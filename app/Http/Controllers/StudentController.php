<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Daftar siswa + saldo poin terkini.
     * - Saldo diambil dari ledger terbaru (balance_after), fallback 2000 = saldo awal.
     * - Support pencarian ?q= (nama / NISN / NIS).
     */
    public function index()
    {
        $q = request('q');
        $classId = request('class_id');
        $sort = request('sort', 'created_at');
        $order = request('order', 'desc');
        $allowed = ['nis','full_name','status','point'];
        $sort = in_array($sort, $allowed) ? $sort : 'created_at';
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        $students = Student::with(['schoolClass.academicYear', 'pointLedgers' => fn ($qq) => $qq->orderByDesc('id')])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('full_name', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%");
                });
            })
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->when($sort !== 'point', fn ($query) => $query->orderBy($sort, $order))
            ->paginate(20)
            ->withQueryString();

        // Point is computed (not a DB column), sort in-memory on the page
        if ($sort === 'point') {
            $students->getCollection()->sortBy(
                fn ($s) => $s->pointLedgers->first()?->balance_after ?? 2000,
                SORT_REGULAR,
                $order === 'desc'
            );
        }

        $classesForPromote = SchoolClass::orderBy('name')->pluck('name', 'id');
        $allClasses = SchoolClass::orderBy('name')->get();

        return view('students.index', compact('students', 'classesForPromote', 'classId', 'allClasses', 'sort', 'order'));
    }

    /** Form tambah siswa — kirim daftar kelas dari controller (jangan query di Blade). */
    public function create()
    {
        $classes = SchoolClass::with('academicYear')->orderBy('name')->get();

        return view('students.create', compact('classes'));
    }

    /** Simpan siswa baru + buat saldo awal 2000 (OPENING_BALANCE) sekali per tahun ajaran. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nisn'      => 'required|string|max:20|unique:students',
            'nis'       => 'required|string|max:20|unique:students',
            'full_name' => 'required|string|max:100',
            'class_id'  => 'required|exists:classes,id',
            'status'    => 'required|in:active,inactive,graduated,transferred',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // Opsional: buat user login jika email diisi (untuk role siswa)
            $user = null;
            if ($request->filled('email')) {
                $user = \App\Models\User::firstOrCreate(
                    ['email' => $request->email],
                    ['name' => $validated['full_name'], 'password' => bcrypt('password')]
                );
            }

            $student = Student::create([
                'user_id'   => $user?->id,
                'class_id'  => $validated['class_id'],
                'nisn'      => $validated['nisn'],
                'nis'       => $validated['nis'],
                'full_name' => $validated['full_name'],
                'status'    => $validated['status'],
            ]);

            // Saldo awal 2000 — hanya sekali per siswa per tahun ajaran (idempotent)
            $this->ensureOpeningBalance($student);

            return redirect()->route('students.index')
                ->with('success', 'Siswa ' . $validated['full_name'] . ' berhasil ditambahkan.');
        });
    }

    /** Form edit siswa */
    public function edit(Student $student)
    {
        $classes = SchoolClass::with('academicYear')->orderBy('name')->get();

        return view('students.edit', compact('student', 'classes'));
    }

    /** Update data siswa */
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'nisn'      => 'sometimes|required|string|max:20|unique:students,nisn,' . $student->id,
            'nis'       => 'sometimes|required|string|max:20|unique:students,nis,' . $student->id,
            'full_name' => 'sometimes|required|string|max:100',
            'class_id'  => 'sometimes|exists:classes,id',
            'status'    => 'sometimes|in:active,inactive,graduated,transferred',
        ]);

        $student->update($validated);

        // Jika pindah kelas / tahun ajaran baru, pastikan opening balance ada
        if (isset($validated['class_id'])) {
            $this->ensureOpeningBalance($student->fresh(['schoolClass']));
        }

        return redirect()->route('students.index')
            ->with('success', 'Data siswa ' . $student->full_name . ' berhasil diupdate.');
    }

    /** Hapus siswa */
    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Siswa ' . $student->full_name . ' berhasil dihapus.');
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

        $students = Student::with('schoolClass')
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
                'kelas' => $s->schoolClass?->name ?? '-',
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
        $headers = ['NISN', 'NIS', 'Nama Lengkap', 'Kelas (nama)', 'Tahun Ajaran (nama)', 'Status'];
        $example = ['0012345678', '12345', 'Budi Santoso', 'X PPLG 1', '2025/2026', 'active'];

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
        $students = Student::with(['schoolClass.academicYear', 'pointLedgers' => fn ($q) => $q->orderByDesc('id')])
            ->latest('nisn')
            ->limit(5000)
            ->get();

        $filename = 'siswa-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['NISN', 'NIS', 'Nama Lengkap', 'Kelas', 'Tahun Ajaran', 'Status', 'Poin Saat Ini']);

            foreach ($students as $s) {
                $poin = $s->pointLedgers->first()?->balance_after ?? 2000;
                fputcsv($out, [
                    $s->nisn,
                    $s->nis,
                    $s->full_name,
                    $s->schoolClass?->name ?? '-',
                    $s->schoolClass?->academicYear?->name ?? '-',
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

                // Resolve kelas — buat jika belum ada
                $classId = null;
                if ($kelasNama !== '') {
                    $year = $tahunNama !== '' ? AcademicYear::firstOrCreate(['name' => $tahunNama], ['start_date' => now(), 'is_active' => false]) : $activeYear;
                    if ($year) {
                        $kelas = SchoolClass::firstOrCreate(
                            ['name' => $kelasNama, 'academic_year_id' => $year->id],
                            ['homeroom_teacher_id' => null]
                        );
                        $classId = $kelas->id;
                    }
                }
                // Fallback: pakai kelas pertama jika CSV tidak sebut kelas
                if (! $classId) {
                    $classId = SchoolClass::orderBy('name')->value('id');
                }
                if (! $classId) {
                    $errors[] = "Baris {$rowNum} ({$nama}): tidak ada kelas — buat kelas dulu di menu Kelas.";
                    $skipped++;
                    continue;
                }

                $student = Student::create([
                    'nisn'      => $nisn,
                    'nis'       => $nis,
                    'full_name' => $nama,
                    'class_id'  => $classId,
                    'status'    => $status,
                ]);

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
        $student->loadMissing('schoolClass');
        $yearId = $student->schoolClass?->academic_year_id
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

        PointLedger::create([
            'student_id'       => $student->id,
            'academic_year_id' => $yearId,
            'direction'        => PointLedger::DIR_CREDIT,
            'amount'           => (int) PointLedger::OPENING_AMOUNT,
            'balance_after'    => (int) PointLedger::OPENING_AMOUNT,
            'transaction_type' => PointLedger::TYPE_OPENING,
            'source_type'      => null,
            'source_id'        => null,
            'reason'           => 'Saldo awal tahun ajaran',
            'created_by'       => auth()->id(),
            'verified_by'      => auth()->id(),
            'verified_at'      => now(),
        ]);
    }
}
