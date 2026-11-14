<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NfcCardController extends Controller
{
    /** Daftar role yang dianggap staf sekolah. User tanpa role apa pun = DITOLAK. */
    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    /**
     * Pastikan pengguna adalah staf sekolah.
     */
    private function authorizeStaff(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::STAFF_ROLES),
            403,
            'Akses ditolak. Fitur ini hanya untuk staf sekolah.'
        );
    }

    private const MANAGER_ROLES = ['admin', 'bk', 'kesiswaan', 'kepala_sekolah'];

    /**
     * Pastikan pengguna adalah staf manajemen (admin, kesiswaan, kepala sekolah).
     */
    private function authorizeManagement(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(self::MANAGER_ROLES),
            403,
            'Hanya admin / kesiswaan / kepala sekolah yang boleh mengubah data nametag NFC.',
        );
    }

    /**
     * Tampilkan tabel data nametag NFC yang dicocokkan dengan siswa.
     */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'all');
        $classId = $request->query('class_id');
        $sort = $request->query('sort', 'full_name');
        $order = strtolower($request->query('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $totalStudents = Student::count();
        $assignedCount = Student::whereNotNull('nfc_uid')->where('nfc_uid', '!=', '')->count();
        $unassignedCount = $totalStudents - $assignedCount;

        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $classes = SchoolClass::when($activeYearId, fn ($query) => $query->where('academic_year_id', $activeYearId))
            ->orderBy('name')
            ->get();

        $query = Student::query()
            ->with(['currentEnrollment.schoolClass', 'pointLedgers']);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('full_name', 'like', "%{$q}%")
                    ->orWhere('nis', 'like', "%{$q}%")
                    ->orWhere('nisn', 'like', "%{$q}%")
                    ->orWhere('nfc_uid', 'like', "%{$q}%");
            });
        }

        if ($status === 'assigned') {
            $query->whereNotNull('nfc_uid')->where('nfc_uid', '!=', '');
        } elseif ($status === 'unassigned') {
            $query->where(function ($sub) {
                $sub->whereNull('nfc_uid')->orWhere('nfc_uid', '');
            });
        }

        if ($classId) {
            $query->whereHas('currentEnrollment', fn ($sub) => $sub->where('class_id', $classId));
        }

        $allowedSorts = ['id', 'nis', 'nisn', 'full_name', 'nfc_uid', 'created_at'];
        $sortColumn = in_array($sort, $allowedSorts, true) ? $sort : 'full_name';

        $students = $query->orderBy($sortColumn, $order)->paginate(25)->withQueryString();

        return view('nametag-nfc.index', compact(
            'students',
            'totalStudents',
            'assignedCount',
            'unassignedCount',
            'classes',
            'q',
            'status',
            'classId',
            'sort',
            'order'
        ));
    }

    /**
     * Tampilkan form pasangkan nametag NFC baru ke siswa.
     */
    public function create()
    {
        $this->authorizeManagement();

        $studentsWithoutNfc = Student::query()
            ->where(function ($q) {
                $q->whereNull('nfc_uid')->orWhere('nfc_uid', '');
            })
            ->with(['currentEnrollment.schoolClass'])
            ->orderBy('full_name')
            ->get();

        return view('nametag-nfc.create', compact('studentsWithoutNfc'));
    }

    /**
     * Simpan pemasangan nametag NFC ke siswa.
     */
    public function store(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'nfc_uid' => 'required|string|max:50|unique:students,nfc_uid',
        ], [
            'student_id.required' => 'Siswa wajib dipilih.',
            'student_id.exists' => 'Siswa tidak ditemukan.',
            'nfc_uid.required' => 'Kode Unik / UID NFC wajib diisi.',
            'nfc_uid.unique' => 'Kode UID NFC ini sudah digunakan oleh siswa lain.',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $student->update([
            'nfc_uid' => trim($validated['nfc_uid']),
        ]);

        return redirect()->route('nametag-nfc.index')
            ->with('success', "Nametag NFC {$student->nfc_uid} berhasil dipasangkan ke siswa {$student->full_name}.");
    }

    /**
     * Tampilkan form edit / ganti nametag NFC siswa.
     */
    public function edit(Student $student)
    {
        $this->authorizeManagement();

        $student->load(['currentEnrollment.schoolClass']);

        return view('nametag-nfc.edit', compact('student'));
    }

    /**
     * Update kode unik nametag NFC siswa.
     */
    public function update(Request $request, Student $student)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'nfc_uid' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('students', 'nfc_uid')->ignore($student->id),
            ],
        ], [
            'nfc_uid.unique' => 'Kode UID NFC ini sudah digunakan oleh siswa lain.',
        ]);

        $cleanUid = $request->filled('nfc_uid') ? trim($validated['nfc_uid']) : null;
        $student->update(['nfc_uid' => $cleanUid]);

        $msg = $cleanUid
            ? "Kode UID Nametag NFC siswa {$student->full_name} berhasil diperbarui menjadi {$cleanUid}."
            : "Nametag NFC siswa {$student->full_name} berhasil dilepas.";

        return redirect()->route('nametag-nfc.index')->with('success', $msg);
    }

    /**
     * Lepaskan nametag NFC dari siswa.
     */
    public function destroy(Student $student)
    {
        $this->authorizeManagement();

        $oldUid = $student->nfc_uid;
        $student->update(['nfc_uid' => null]);

        return redirect()->route('nametag-nfc.index')
            ->with('success', "Nametag NFC ({$oldUid}) siswa {$student->full_name} berhasil dilepaskan.");
    }

    /**
     * Export data nametag NFC ke format XLSX.
     */
    public function export()
    {
        $this->authorizeStaff();

        $students = Student::with(['currentEnrollment.schoolClass'])
            ->orderBy('full_name')
            ->get();

        $rows = [
            ['No', 'UID Nametag NFC', 'NIS', 'NISN', 'Nama Siswa', 'Kelas', 'Saldo Poin', 'Status Nametag'],
        ];

        foreach ($students as $index => $s) {
            $hasNfc = ! empty($s->nfc_uid);
            $rows[] = [
                (string) ($index + 1),
                (string) ($s->nfc_uid ?? '-'),
                (string) $s->nis,
                (string) $s->nisn,
                (string) $s->full_name,
                (string) ($s->currentEnrollment?->schoolClass?->name ?? '-'),
                (string) $s->current_balance,
                $hasNfc ? 'Terpasang' : 'Belum Terpasang',
            ];
        }

        $path = Xlsx::write($rows, 'data-nametag-nfc', true);
        $filename = 'data-nametag-nfc-' . date('Ymd-His') . '.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Download template XLSX untuk import nametag NFC.
     */
    public function downloadTemplate()
    {
        $this->authorizeStaff();

        $sampleStudents = Student::query()
            ->with(['currentEnrollment.schoolClass'])
            ->limit(3)
            ->get();

        $rows = [
            ['NIS (Wajib)', 'Nama Siswa (Referensi)', 'UID Nametag NFC (Wajib)'],
        ];

        if ($sampleStudents->isNotEmpty()) {
            foreach ($sampleStudents as $idx => $s) {
                $dummyUid = '04A2' . str_pad((string) ($idx + 1), 6, '0', STR_PAD_LEFT);
                $rows[] = [
                    (string) $s->nis,
                    (string) $s->full_name,
                    $dummyUid,
                ];
            }
        } else {
            $rows[] = ['NIS-001', 'Budi Santoso', '04A23B891F6080'];
        }

        $path = Xlsx::write($rows, 'template-nametag', true);

        return response()->download($path, 'template-import-nametag-nfc.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import pencocokan data nametag NFC dari file XLSX / CSV.
     */
    public function import(Request $request)
    {
        $this->authorizeManagement();

        $request->validate([
            'file' => 'required|file|max:4096',
        ], [
            'file.required' => 'Pilih file XLSX atau CSV yang ingin diimpor.',
            'file.max' => 'Ukuran file maksimal 4MB.',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            return back()->with('error', 'Format file tidak didukung. Harap unggah file .xlsx atau .csv.');
        }

        try {
            if ($ext === 'xlsx') {
                $rows = Xlsx::read($file->getRealPath());
            } else {
                $rows = [];
                $handle = fopen($file->getRealPath(), 'r');
                if ($handle !== false) {
                    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                        $rows[] = $data;
                    }
                    fclose($handle);
                }
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca isi file: ' . $e->getMessage());
        }

        if (count($rows) < 2) {
            return back()->with('error', 'File tidak berisi data atau baris kosong.');
        }

        $header = array_map(fn ($col) => mb_strtolower(trim((string) $col)), array_shift($rows));

        // Deteksi index kolom
        $nisIdx = null;
        $uidIdx = null;

        foreach ($header as $idx => $name) {
            if (str_contains($name, 'nis') && ! str_contains($name, 'nisn')) {
                $nisIdx = $idx;
            } elseif (str_contains($name, 'uid') || str_contains($name, 'nfc') || str_contains($name, 'tag')) {
                $uidIdx = $idx;
            }
        }

        // Fallback default index bila nama kolom format baku
        if ($nisIdx === null) {
            $nisIdx = 0;
        }
        if ($uidIdx === null) {
            $uidIdx = count($header) >= 3 ? 2 : 1;
        }

        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $rIdx => $row) {
                $rowNumber = $rIdx + 2;
                $nis = trim((string) ($row[$nisIdx] ?? ''));
                $uid = trim((string) ($row[$uidIdx] ?? ''));

                if ($nis === '' || $uid === '') {
                    $skipped++;
                    continue;
                }

                $student = Student::where('nis', $nis)->orWhere('nisn', $nis)->first();

                if (! $student) {
                    $skipped++;
                    $errors[] = "Baris {$rowNumber}: Siswa dengan NIS/NISN '{$nis}' tidak ditemukan.";
                    continue;
                }

                // Cek apakah UID sudah dipakai oleh siswa lain
                $existing = Student::whereRaw('LOWER(nfc_uid) = ?', [strtolower($uid)])
                    ->where('id', '!=', $student->id)
                    ->first();

                if ($existing) {
                    $skipped++;
                    $errors[] = "Baris {$rowNumber}: UID '{$uid}' sudah dipakai oleh {$existing->full_name}.";
                    continue;
                }

                $student->update(['nfc_uid' => $uid]);
                $updated++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses import: ' . $e->getMessage());
        }

        $msg = "Import selesai: {$updated} nametag NFC berhasil dicocokkan.";
        if ($skipped > 0) {
            $msg .= " ({$skipped} data dilewati).";
        }
        if (! empty($errors)) {
            $msg .= ' Catatan: ' . implode(' | ', array_slice($errors, 0, 3));
        }

        return redirect()->route('nametag-nfc.index')->with('success', $msg);
    }
}
