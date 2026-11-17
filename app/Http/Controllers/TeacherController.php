<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    private const MANAGER_ROLES = ['admin', 'kesiswaan', 'kepala_sekolah'];

    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    /** Role akun yang boleh ditautkan ke data guru. */
    private const TEACHER_ROLES = ['guru', 'wali_kelas'];

    private function authorizeStaff(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::STAFF_ROLES),
            403,
            'Akses ditolak. Fitur ini hanya untuk staf sekolah.',
        );
    }

    private function authorizeManagement(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::MANAGER_ROLES),
            403,
            'Hanya admin / kesiswaan / kepala sekolah yang boleh mengubah data guru.',
        );
    }

    /** Daftar master guru. */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $sort = $request->string('sort')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'nip', 'full_name', 'mata_pelajaran', 'status', 'created_at'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'full_name';

        $teachers = Teacher::with('user')
            ->when($q, fn ($query) => $query->where(function ($where) use ($q) {
                $where->where('full_name', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%")
                    ->orWhere('mata_pelajaran', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('email', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%"));
            }))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $order)
            ->paginate(20)
            ->withQueryString();

        return view('teachers.index', compact('teachers', 'q', 'status', 'sort', 'order'));
    }

    public function create()
    {
        $this->authorizeManagement();

        $accounts = $this->availableAccounts();

        return view('teachers.create', compact('accounts'));
    }

    /** Simpan data guru baru (opsional ditautkan ke akun pengguna). */
    public function store(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:40', 'unique:teachers,nip'],
            'mata_pelajaran' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:active,inactive'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:teachers,user_id'],
        ]);

        $validated['status'] ??= 'active';

        $teacher = Teacher::create($validated);

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Data guru '.$teacher->full_name.' berhasil ditambahkan.');
    }

    public function edit(Teacher $teacher)
    {
        $this->authorizeManagement();

        $accounts = $this->availableAccounts($teacher);

        return view('teachers.edit', compact('teacher', 'accounts'));
    }

    /** Perbarui data guru. */
    public function update(Request $request, Teacher $teacher)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:40', Rule::unique('teachers', 'nip')->ignore($teacher->id)],
            'mata_pelajaran' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:active,inactive'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('teachers', 'user_id')->ignore($teacher->id)],
        ]);

        $validated['status'] ??= 'active';

        $teacher->update($validated);

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Data guru '.$teacher->full_name.' berhasil diperbarui.');
    }

    /** Toggle status aktif/nonaktif — guru tidak dihapus agar riwayat tetap utuh. */
    public function toggleStatus(Teacher $teacher)
    {
        $this->authorizeManagement();

        $teacher->status = $teacher->status === 'active' ? 'inactive' : 'active';
        $teacher->save();

        return back()->with(
            'success',
            'Data guru '.$teacher->full_name.' berhasil '.($teacher->status === 'active' ? 'diaktifkan' : 'dinonaktifkan').'.',
        );
    }

    // ────────────────────────────────────────────────
    // Import / export XLSX & CSV (mengikuti pola data siswa)
    // ────────────────────────────────────────────────

    /** Template import guru. */
    public function downloadTemplate()
    {
        $this->authorizeManagement();

        $rows = [
            ['NIP', 'Nama Lengkap', 'Mata Pelajaran', 'Telepon', 'Status'],
            ['198501012010011001', 'Budi Santoso', 'Matematika', '08123456789', 'active'],
        ];

        $path = Xlsx::write($rows, 'template-guru', true);

        return response()->download($path, 'template-guru.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Export master guru ke XLSX. */
    public function export()
    {
        $this->authorizeManagement();

        $teachers = Teacher::with('user')
            ->orderBy('full_name')
            ->limit(5000)
            ->get();

        $rows = [
            ['NIP', 'Nama Lengkap', 'Mata Pelajaran', 'Telepon', 'Email Akun', 'Status'],
        ];

        foreach ($teachers as $teacher) {
            $rows[] = [
                $teacher->nip ?? '',
                $teacher->full_name,
                $teacher->mata_pelajaran ?? '',
                $teacher->phone ?? '',
                $teacher->user?->email ?? '',
                $teacher->status,
            ];
        }

        $path = Xlsx::write($rows, 'Daftar Guru', true);
        $filename = 'guru-'.now()->format('Ymd-His').'.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import guru dari CSV/XLSX. Wajib: Nama Lengkap.
     * Opsional: NIP (unik), Mata Pelajaran, Telepon, Status.
     * NIP yang sudah ada → baris dilewati (idempoten).
     */
    public function import(Request $request)
    {
        $this->authorizeManagement();

        $request->validate([
            'file' => ['required', 'file', 'max:4096'],
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, ['csv', 'txt', 'xlsx'], true)) {
            return back()->with('error', 'Format tidak didukung: .'.($ext ?: '(tanpa ekstensi)')
                .' — gunakan berkas .csv, .txt, atau .xlsx.');
        }

        try {
            $rows = $ext === 'xlsx'
                ? Xlsx::read($file->getRealPath())
                : $this->readDelimitedRows(file_get_contents($file->getRealPath()));
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca berkas: '.$e->getMessage());
        }

        if ($rows === []) {
            return back()->with('error', 'Berkas kosong — tidak ada baris untuk diimpor.');
        }

        $header = array_shift($rows);
        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $header);
        $idx = fn ($name) => array_search(mb_strtolower($name), $header, true);

        $iNama = $idx('nama lengkap') !== false ? $idx('nama lengkap') : $idx('nama');
        $iNip = $idx('nip');
        $iMapel = $idx('mata pelajaran');
        $iPhone = $idx('telepon');
        $iStatus = $idx('status');

        if ($iNama === false) {
            $seen = array_values(array_filter($header, fn ($h) => $h !== ''));

            return back()->with('error',
                'Header tidak valid. Ditemukan: ['.($seen ? implode(', ', $seen) : 'tidak ada kolom terbaca').']'
                .'. Kolom wajib: [Nama Lengkap].');
        }

        $imported = 0;
        $skipped = 0;
        $issues = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $offset => $row) {
                $rowNum = $offset + 2;
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $nama = trim((string) ($row[$iNama] ?? ''));
                $nip = $iNip !== false ? trim((string) ($row[$iNip] ?? '')) : '';
                $mapel = $iMapel !== false ? trim((string) ($row[$iMapel] ?? '')) : '';
                $phone = $iPhone !== false ? trim((string) ($row[$iPhone] ?? '')) : '';
                $status = $iStatus !== false ? mb_strtolower(trim((string) ($row[$iStatus] ?? 'active'))) : 'active';

                if ($nama === '') {
                    $skipped++;
                    $issues[] = "Baris {$rowNum}: Nama Lengkap wajib terisi.";

                    continue;
                }
                if (! in_array($status, ['active', 'inactive'], true)) {
                    $status = 'active';
                }
                if ($nip !== '' && Teacher::where('nip', $nip)->exists()) {
                    $skipped++;
                    $issues[] = "Baris {$rowNum} ({$nama}): NIP {$nip} sudah terdaftar.";

                    continue;
                }

                Teacher::create([
                    'full_name' => $nama,
                    'nip' => $nip !== '' ? $nip : null,
                    'mata_pelajaran' => $mapel !== '' ? $mapel : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'status' => $status,
                ]);
                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        $msg = "Import selesai — {$imported} guru ditambahkan".($skipped ? ", {$skipped} dilewati" : '').'.';
        if ($issues !== []) {
            $msg .= ' '.implode(' ', array_slice($issues, 0, 5));
        }

        return back()->with($imported > 0 ? 'success' : 'error', $msg);
    }

    /** Akun guru yang belum tertaut ke data guru (plus akun milik guru ini saat edit). */
    private function availableAccounts(?Teacher $teacher = null)
    {
        return User::query()
            ->whereHas('roles', fn ($role) => $role->whereIn('name', self::TEACHER_ROLES))
            ->where(function ($query) use ($teacher) {
                $query->whereDoesntHave('teacher');
                if ($teacher?->user_id) {
                    $query->orWhere('users.id', $teacher->user_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'nip']);
    }

    /** Baca CSV/TSV mentah → array baris (BOM & pemisah ; / tab ditangani). */
    private function readDelimitedRows(string $content): array
    {
        if ($content === '') {
            return [];
        }

        if (str_starts_with($content, "\xFF\xFE") || str_starts_with($content, "\xFE\xFF")) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $firstLine = preg_split('/\r\n|\n|\r/', $content)[0] ?? '';
        $delimiter = match (true) {
            substr_count($firstLine, "\t") > substr_count($firstLine, ',') => "\t",
            substr_count($firstLine, ';') > substr_count($firstLine, ',') => ';',
            default => ',',
        };

        $rows = [];
        foreach (preg_split('/\r\n|\n|\r/', $content) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = str_getcsv($line, $delimiter, '"', '');
        }

        return $rows;
    }
}
