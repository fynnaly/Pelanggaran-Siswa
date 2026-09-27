<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    /** Daftar siswa, terpisah dari data poin. */
    public function index()
    {
        $q = request('q');
        $classId = request('class_id');
        $sort = request('sort', 'created_at');
        $order = request('order', 'desc');
        $allowed = ['nis', 'nisn', 'full_name', 'gender', 'status'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'created_at';
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        $academicYears = AcademicYear::orderBy('start_date')->get();
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $selectedYearId = request('year_id') ?: null;
        $filterYearId = $selectedYearId ?: $activeYearId;

        $allClasses = SchoolClass::where('academic_year_id', $filterYearId)->orderBy('name')->get();
        $classesForPromote = $allClasses->pluck('name', 'id');

        $students = Student::with([
                'currentEnrollment.schoolClass',
                'currentEnrollment.academicYear',
                'enrollments.schoolClass',
                'enrollments.academicYear',
            ])
            ->whereHas('enrollments', fn ($q) => $q
                ->when($selectedYearId, fn ($sub) => $sub->where('academic_year_id', $selectedYearId))
                ->when($classId, fn ($sub) => $sub->where('class_id', $classId)))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($where) use ($q) {
                    $where->where('full_name', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%");
                });
            })
            ->orderBy($sort, $order)
            ->paginate(20)
            ->withQueryString();

        return view('students.index', compact(
            'students',
            'classesForPromote',
            'classId',
            'allClasses',
            'sort',
            'order',
            'academicYears',
            'selectedYearId',
        ));
    }

    /** Form tambah siswa — kirim daftar kelas dari controller (jangan query di Blade). */
    public function create()
    {
        $classes = SchoolClass::with('academicYear')
            ->where('academic_year_id', AcademicYear::where('is_active', true)->value('id'))
            ->orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        return view('students.create', compact('classes', 'academicYears'));
    }

    /** Simpan siswa baru + buat saldo awal 2000 (OPENING_BALANCE) sekali per tahun ajaran. */
    public function store(Request $request)
        {
            $validated = $request->validate([
                'nisn'      => 'required|string|max:20|unique:students',
                'nis'       => 'required|string|max:20|unique:students',
                'full_name' => 'required|string|max:100',
                'gender'    => 'required|in:L,P',
                'birth_date'=> 'nullable|date',
                'class_id'  => 'required|exists:classes,id',
                'academic_year_id' => 'required|exists:academic_years,id',
                'phone'     => 'nullable|string|max:20',
                'address'   => 'nullable|string',
                'parent_name' => 'nullable|string|max:100',
                'parent_phone' => 'nullable|string|max:20',
                'status'    => 'required|in:active,inactive,graduated,transferred',
            ]);

        return DB::transaction(function () use ($validated, $request) {
            // Opsional: buat user login jika email diisi (untuk role siswa). Jika tidak diisi, siswa tetap master tanpa akun.
            $user = null;
            if ($request->filled('email')) {
                $user = \App\Models\User::firstOrCreate(
                    ['email' => $request->email],
                    ['name' => $validated['full_name'], 'password' => Hash::make(\Illuminate\Support\Str::random(16))]
                );
                if (! $user->hasRole('siswa')) {
                    $user->assignRole('siswa');
                }
            }

            $student = Student::create([
                'user_id'   => $user?->id,
                'nisn'      => $validated['nisn'],
                'nis'       => $validated['nis'],
                'full_name' => $validated['full_name'],
                'gender'    => $validated['gender'],
                'birth_date'=> $validated['birth_date'],
                'phone'     => $validated['phone'] ?? null,
                'address'   => $validated['address'] ?? null,
                'parent_name' => $validated['parent_name'] ?? null,
                'parent_phone' => $validated['parent_phone'] ?? null,
                'status'    => $validated['status'],
            ]);

            // Buat enrollment: siswa terdaftar di kelas/tahun ajaran
            $this->syncEnrollment($student, $validated['class_id'], $validated['academic_year_id']);

            // Saldo awal 2000 — idempotent per siswa per tahun ajaran
            $this->ensureOpeningBalance($student);

            return redirect()->route('students.index')
                ->with('success', 'Siswa ' . $validated['full_name'] . ' berhasil ditambahkan.');
        });
    }

    /** Form edit siswa */
    public function edit(Student $student)
    {
        $classes = SchoolClass::with('academicYear')
            ->where('academic_year_id', AcademicYear::where('is_active', true)->value('id'))
            ->orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('start_date')->get();

        return view('students.edit', compact('student', 'classes', 'academicYears'));
    }

    /** Detail siswa (master only) */
    public function show(Student $student)
    {
        $student->load([
            'currentEnrollment.schoolClass',
            'currentEnrollment.academicYear',
            'enrollments.schoolClass',
            'enrollments.academicYear',
        ]);
        return view('students.show', compact('student'));
    }

    /** Update data siswa */
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'nisn'      => 'sometimes|string|max:20|unique:students,nisn,' . $student->id,
            'nis'       => 'sometimes|string|max:20|unique:students,nis,' . $student->id,
            'full_name' => 'sometimes|string|max:100',
            'gender'    => 'sometimes|in:L,P',
            'birth_date'=> 'nullable|date',
            'class_id'  => 'sometimes|exists:classes,id',
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'phone'     => 'nullable|string|max:20',
            'address'   => 'nullable|string',
            'parent_name' => 'nullable|string|max:100',
            'parent_phone' => 'nullable|string|max:20',
            'status'    => 'sometimes|in:active,inactive,graduated,transferred',
        ]);

        $student->update($validated);

        // Sinkronisasi enrollment jika pindah kelas/tahun ajaran
        $enroll  = $student->latestEnrollment();
        $resolvedClassId = $validated['class_id'] ?? $enroll?->class_id;
        $resolvedYearId  = $validated['academic_year_id'] ?? $enroll?->academic_year_id;
        if ((isset($validated['class_id']) || isset($validated['academic_year_id'])) && $resolvedClassId && $resolvedYearId) {
            $this->syncEnrollment($student, $resolvedClassId, $resolvedYearId);
        }

        // Jika pindah kelas / tahun ajaran baru, pastikan opening balance ada
        if (isset($validated['class_id']) || isset($validated['academic_year_id'])) {
            $this->ensureOpeningBalance($student->fresh(['enrollments.schoolClass', 'enrollments.academicYear']));
        }

        return redirect()->route('students.index')
            ->with('success', 'Data siswa ' . $student->full_name . ' berhasil diupdate.');
    }

    /** Hapus siswa (tidak diaktifkan — siswa tetap bisa diedit/ditampilkan) */
    public function destroy(Student $student)
    {
        // Delete disabled per rencana: siswa bisa show/edit tanpa hapus
        return redirect()->route('students.index')
            ->with('error', 'Penghapusan siswa dinonaktifkan.');
    }

    /**
     * Endpoint JSON untuk autocomplete pencarian siswa.
     * Mencari berdasarkan nama, NISN, atau NIS.
     */
    public function search(Request $request)
    {
        $q = $request->input('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $students = Student::with(['currentEnrollment.schoolClass'])
            ->where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('full_name', 'like', "%{$q}%")
                    ->orWhere('nisn', 'like', "%{$q}%")
                    ->orWhere('nis', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'full_name' => $s->full_name,
                'nis' => $s->nis,
                'kelas' => $s->currentEnrollment?->schoolClass?->name ?? '-',
            ]);

        return response()->json($students);
    }

    // ────────────────────────────────────────────────
    // CSV Native — tanpa library (maatwebsite gagal di PHP 8.5.5 ext-gd)
    // Per docs/UJIKOM-v2.0.md: Export = CSV native
    // ────────────────────────────────────────────────

    /** Download template CSV kosong untuk import siswa */
    public function downloadTemplate()
    {
        $headers = ['NISN', 'NIS', 'Nama Lengkap', 'Jenis Kelamin', 'Telepon', 'Alamat', 'Nama Orang Tua', 'No. HP Orang Tua', 'Kelas (nama)', 'Tahun Ajaran (nama)', 'Status'];
        $example = ['0012345678', '12345', 'Budi Santoso', 'Laki-laki', '08123456789', 'Jl. Merdeka No. 1', 'Bapak Santoso', '08987654321', 'X PPLG 1', '2025/2026', 'active'];

        return response()->streamDownload(function () use ($headers, $example) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel Windows baca UTF-8 dengan benar
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, $headers);
            fputcsv($out, $example);
            fclose($out);
        }, 'template-siswa.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Export daftar siswa ke CSV (paginate 5000, include poin terkini) */
    public function export()
    {
        $students = Student::with(['currentEnrollment.schoolClass', 'currentEnrollment.academicYear', 'pointLedgers' => fn ($q) => $q->orderByDesc('id')])
            ->latest('nisn')
            ->limit(5000)
            ->get();

        $filename = 'siswa-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['NISN', 'NIS', 'Nama Lengkap', 'Jenis Kelamin', 'Telepon', 'Alamat', 'Nama Orang Tua', 'No. HP Orang Tua', 'Kelas', 'Tahun Ajaran', 'Status', 'Poin Saat Ini']);

            foreach ($students as $s) {
                $poin = $s->pointLedgers->first()?->balance_after ?? 2000;
                fputcsv($out, [
                    $s->nisn,
                    $s->nis,
                    $s->full_name,
                    $s->gender === 'L' ? 'Laki-laki' : 'Perempuan',
                    $s->phone ?? '',
                    $s->address ?? '',
                    $s->parent_name ?? '',
                    $s->parent_phone ?? '',
                    $s->currentEnrollment?->schoolClass?->name ?? '-',
                    $s->currentEnrollment?->academicYear?->name ?? '-',
                    $s->status,
                    $poin,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Import siswa dari CSV.
     * Kolom wajib: NISN, NIS, Nama Lengkap. Opsional: Kelas (nama), Tahun Ajaran (nama), Status.
     * - Jika NISN sudah ada → skip (idempotent).
     * - Jika Kelas tidak ditemukan → buat otomatis di tahun ajaran aktif.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if (! $handle) {
            return back()->with('error', 'Gagal membaca file CSV.');
        }

        // Lewati BOM jika ada
        $first = fgets($handle);
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $header = str_getcsv(trim($first));
        $header = array_map(fn ($h) => trim(strtolower($h)), $header);

        // Mapping header → index
        $idx = fn ($name) => array_search(strtolower($name), $header, true);

        $iNisn   = $idx('nisn');
        $iNis    = $idx('nis');
        $iNama   = $idx('nama lengkap') !== false ? $idx('nama lengkap') : $idx('nama');
        $iGender = $idx('jenis kelamin');
        $iPhone  = $idx('telepon');
        $iAddr   = $idx('alamat');
        $iParent = $idx('nama orang tua');
        $iParentHp = $idx('no. hp orang tua');
        $iKelas  = $idx('kelas (nama)') !== false ? $idx('kelas (nama)') : $idx('kelas');
        $iTahun  = $idx('tahun ajaran (nama)') !== false ? $idx('tahun ajaran (nama)') : $idx('tahun ajaran');
        $iStatus = $idx('status');

        if ($iNisn === false || $iNis === false || $iNama === false) {
            fclose($handle);
            return back()->with('error', 'Header CSV tidak valid. Wajib ada kolom: NISN, NIS, Nama Lengkap.');
        }

        $activeYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::orderByDesc('id')->first();

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        DB::beginTransaction();
        try {
            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $nisn = trim($row[$iNisn] ?? '');
                $nis  = trim($row[$iNis] ?? '');
                $nama = trim($row[$iNama] ?? '');
                $gender = $iGender !== false ? strtoupper(trim($row[$iGender] ?? '')) : '';
                if (! in_array($gender, ['L', 'P'], true)) {
                    $gender = str_starts_with(strtolower($gender), 'l') ? 'L' : (str_starts_with(strtolower($gender), 'p') ? 'P' : 'L');
                }
                $phone = $iPhone !== false ? trim($row[$iPhone] ?? '') : '';
                $address = $iAddr !== false ? trim($row[$iAddr] ?? '') : '';
                $parentName = $iParent !== false ? trim($row[$iParent] ?? '') : '';
                $parentHp = $iParentHp !== false ? trim($row[$iParentHp] ?? '') : '';
                $kelasNama = $iKelas !== false ? trim($row[$iKelas] ?? '') : '';
                $tahunNama = $iTahun !== false ? trim($row[$iTahun] ?? '') : '';
                $status = $iStatus !== false ? trim(strtolower($row[$iStatus] ?? 'active')) : 'active';

                if ($nisn === '' || $nis === '' || $nama === '') {
                    $errors[] = "Baris {$rowNum}: NISN/NIS/Nama kosong — dilewati.";
                    $skipped++;
                    continue;
                }
                if (! in_array($status, ['active', 'inactive', 'graduated', 'transferred'], true)) {
                    $status = 'active';
                }
                if (Student::where('nisn', $nisn)->exists() || Student::where('nis', $nis)->exists()) {
                    $skipped++;
                    continue;
                }

                // Resolve kelas + tahun ajaran — buat otomatis bila belum ada
                $classId = null;
                $yearId  = $activeYear?->id;
                if ($kelasNama !== '') {
                    $year = $tahunNama !== ''
                        ? AcademicYear::firstOrCreate(['name' => $tahunNama], ['start_date' => now(), 'is_active' => false])
                        : $activeYear;
                    if ($year) {
                        $kelas = SchoolClass::firstOrCreate(
                            ['name' => $kelasNama, 'academic_year_id' => $year->id],
                            ['homeroom_teacher_id' => null]
                        );
                        $classId = $kelas->id;
                        $yearId  = $year->id;
                    }
                }
                // Fallback: pakai kelas pertama + tahun ajarannya
                if (! $classId) {
                    $fallback = SchoolClass::with('academicYear')->orderBy('name')->first();
                    $classId  = $fallback?->id;
                    $yearId   = $fallback?->academic_year_id ?? $yearId;
                }
                if (! $classId || ! $yearId) {
                    $errors[] = "Baris {$rowNum} ({$nama}): tidak ada kelas/tahun ajaran — buat kelas dulu di menu Kelas.";
                    $skipped++;
                    continue;
                }

                $student = Student::create([
                    'nisn'        => $nisn,
                    'nis'         => $nis,
                    'full_name'   => $nama,
                    'gender'      => $gender,
                    'phone'       => $phone ?: null,
                    'address'     => $address ?: null,
                    'parent_name' => $parentName ?: null,
                    'parent_phone'=> $parentHp ?: null,
                    'status'      => $status,
                ]);

                $this->syncEnrollment($student, $classId, $yearId);
                $this->ensureOpeningBalance($student);
                $imported++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        fclose($handle);

        $msg = "Import selesai — {$imported} siswa ditambahkan" . ($skipped ? ", {$skipped} dilewati" : "") . ".";
        if ($errors) {
            $msg .= ' ' . implode(' ', array_slice($errors, 0, 3));
        }

        return back()->with('success', $msg);
    }

    /**
     * Pastikan ledger OPENING_BALANCE 2000 ada — idempotent per siswa per tahun ajaran.
     */
    private function ensureOpeningBalance(Student $student): void
    {
        $enroll  = $student->latestEnrollment();
        $yearId  = $enroll?->academic_year_id
            ?? AcademicYear::where('is_active', true)->value('id')
            ?? AcademicYear::orderByDesc('id')->value('id');

        if (! $yearId) {
            return;
        }

        $exists = PointLedger::where('student_id', $student->id)
            ->where('academic_year_id', $yearId)
            ->where('transaction_type', PointLedger::TYPE_OPENING)
            ->exists();

        if ($exists) {
            return;
        }

        $lastBalance = PointLedger::getLastBalance($student->id);
        $newBalance = $lastBalance;

        PointLedger::create([
            'student_id'       => $student->id,
            'academic_year_id' => $yearId,
            'direction'        => PointLedger::DIR_CREDIT,
            'amount'           => (int) PointLedger::OPENING_AMOUNT,
            'balance_after'    => $newBalance,
            'transaction_type' => PointLedger::TYPE_OPENING,
            'source_type'      => null,
            'source_id'        => null,
            'reason'           => 'Saldo awal tahun ajaran',
            'created_by'       => auth()->id(),
            'verified_by'      => auth()->id(),
            'verified_at'      => now(),
        ]);
    }

    /**
     * Sinkronisasi enrollment: upsert baris enrollment untuk (student, tahun ajaran) saat ini.
     * Idempotent — dipanggil dari store, update, dan import.
     */
    private function syncEnrollment(Student $student, int $classId, int $academicYearId): void
    {
        Enrollment::updateOrCreate(
            [
                'student_id' => $student->id,
                'academic_year_id' => $academicYearId,
            ],
            [
                'class_id' => $classId,
                'status' => $this->mapEnrollmentStatus($student->status),
                'enrolled_at' => $student->created_at ?? now(),
            ]
        );
    }

    private function mapEnrollmentStatus(string $studentStatus): string
    {
        return match ($studentStatus) {
            'inactive' => 'withdrawn',
            default => $studentStatus,
        };
    }
}
