<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /** Alias umum yang sering dipakai pengguna — dinormalisasi ke role resmi. */
    private const ROLE_ALIASES = [
        'murid' => 'siswa',
        'student' => 'siswa',
        'teacher' => 'guru',
        'wali kelas' => 'wali_kelas',
        'walikelas' => 'wali_kelas',
        'wali-kelas' => 'wali_kelas',
    ];

    private const STAFF_ROLES = [
        'guru',
        'wali_kelas',
        'bk',
        'kesiswaan',
        'kepala_sekolah',
        'admin',
    ];

    private const MANAGER_ROLES = [
        'admin',
        'kepala_sekolah',
        'kesiswaan',
    ];

    /**
     * Display user accounts with Guru/Siswa filters.
     */
    public function index()
    {
        $type = request('type');
        $jabatan = request('jabatan');
        $q = request('q');

        $roles = Role::whereIn('name', self::STAFF_ROLES)->orderBy('name')->pluck('name');

        $sort = request()->string('sort')->toString();
        $order = strtolower(request()->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'name', 'email', 'created_at'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'name';

        $users = User::with(['roles', 'student.currentEnrollment.schoolClass'])
            ->when($q, fn ($query) => $query->where(function ($where) use ($q) {
                $where->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhereHas('student', fn ($student) => $student
                        ->where('full_name', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%"));
            }))
            ->when($type === 'guru', fn ($query) => $query->whereHas(
                'roles',
                fn ($role) => $role->whereIn('name', self::STAFF_ROLES),
            ))
            ->when($type === 'siswa', fn ($query) => $query->whereHas(
                'roles',
                fn ($role) => $role->where('name', 'siswa'),
            ))
            ->when($jabatan, fn ($query) => $query->whereHas(
                'roles',
                fn ($role) => $role->where('name', $jabatan),
            ))
            ->orderBy($sort, $order)
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users', 'roles', 'type', 'jabatan', 'q', 'sort', 'order'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->authorizeManagement();

        $roles = Role::orderBy('name')->pluck('name');
        $students = Student::whereNull('user_id')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'nis', 'nisn']);

        return view('users.create', compact('roles', 'students'));
    }

    /**
     * Store a user and optionally link it to one student identity.
     */
    public function store(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
            'student_id' => 'nullable|integer|exists:students,id',
        ]);

        $role = $validated['role'];
        $studentId = $validated['student_id'] ?? null;
        $this->validateStudentLink($role, $studentId);

        $user = DB::transaction(function () use ($validated, $role, $studentId) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->syncRoles([$role]);

            if ($studentId !== null) {
                Student::whereKey($studentId)->update(['user_id' => $user->id]);
            }

            return $user;
        });

        return redirect()->route('users.index')
            ->with('success', 'User ' . $user->name . ' berhasil dibuat.');
    }

    /**
     * Display one user account and its linked student identity.
     */
    public function show(User $user)
    {
        $user->load(['roles', 'student.currentEnrollment.schoolClass', 'student.currentEnrollment.academicYear']);

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user)
    {
        $this->authorizeManagement();

        $roles = Role::orderBy('name')->pluck('name');
        $students = Student::where(function ($query) use ($user) {
            $query->whereNull('user_id')->orWhere('user_id', $user->id);
        })
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'nis', 'nisn']);
        $user->load('roles');

        return view('users.edit', compact('user', 'roles', 'students'));
    }

    /**
     * Update account data, role, and optional student identity link.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|required|exists:roles,name',
            'student_id' => 'nullable|integer|exists:students,id',
        ]);

        $role = $validated['role'] ?? $user->roles->first()?->name;
        $studentId = array_key_exists('student_id', $validated)
            ? $validated['student_id']
            : ($role === 'siswa' ? $user->student?->id : null);
        $this->validateStudentLink($role, $studentId, $user->id);

        DB::transaction(function () use ($validated, $user, $role, $studentId) {
            $userData = collect($validated)->only(['name', 'email', 'password'])->all();
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }
            $user->update($userData);
            $user->syncRoles([$role]);

            Student::where('user_id', $user->id)->update(['user_id' => null]);
            if ($studentId !== null) {
                Student::whereKey($studentId)->update(['user_id' => $user->id]);
            }
        });

        return redirect()->route('users.index')
            ->with('success', 'User ' . $user->name . ' berhasil diperbarui.');
    }

    /**
     * Delete a user after detaching its student identity.
     */
    public function destroy(User $user)
    {
        $this->authorizeManagement();

        if ($user->is(request()->user())) {
            return redirect()->route('users.index')
                ->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        DB::transaction(function () use ($user) {
            Student::where('user_id', $user->id)->update(['user_id' => null]);
            $user->delete();
        });

        return redirect()->route('users.index')
            ->with('success', 'User berhasil dihapus. Identitas siswa tetap tersimpan.');
    }

    // ────────────────────────────────────────────────
    // CSV Native — import/export pengguna
    // ────────────────────────────────────────────────

    /**
     * Download template XLSX untuk import pengguna (file ber-style, siap Excel).
     * Fallback CSV polos bila file belum ada (jalankan Xlsx::write dulu).
     */
    public function downloadTemplate()
    {
        $path = public_path('template-pengguna.xlsx');

        if (is_file($path)) {
            return response()->download($path, 'template-pengguna.xlsx');
        }

        $headers = ['Nama', 'Email', 'Password', 'Role'];
        $example = ['Budi Santoso', 'budi@guru.com', 'rahasia123', 'guru'];

        return response()->streamDownload(function () use ($headers, $example) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, $headers);
            fputcsv($out, $example);
            fclose($out);
        }, 'template-pengguna.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Export pengguna ke CSV */
    public function export()
    {
        $users = User::with(['roles', 'student.currentEnrollment.schoolClass'])
            ->orderBy('name')
            ->limit(5000)
            ->get();

        $filename = 'pengguna-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($users) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Nama', 'Email', 'Role', 'Identitas Siswa', 'Kelas']);

            foreach ($users as $u) {
                $role = $u->roles->first()?->name ?? '-';
                $studentInfo = $u->student ? $u->student->nisn . ' - ' . $u->student->full_name : '-';
                $kelas = $u->student?->currentEnrollment?->schoolClass?->name ?? '-';
                fputcsv($out, [$u->name, $u->email, $role, $studentInfo, $kelas]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Import pengguna dari CSV atau XLSX. Wajib: Nama, Email, Password, Role.
     *
     * Validasi memakai ekstensi, bukan `mimes:csv,txt`: Excel menyimpan .csv
     * dengan MIME application/vnd.ms-excel sehingga rule mime selalu menolak
     * file yang sebenarnya valid. Ekstensi + pembacaan Xlsx/CSV sendiri lebih
     * dapat dipercaya untuk file hasil ekspor spreadsheet.
     */
    public function import(Request $request)
    {
        $this->authorizeManagement();

        $request->validate([
            'file' => 'required|file|max:4096',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['csv', 'txt', 'xlsx'];

        if (! in_array($ext, $allowed, true)) {
            return back()->with('error', 'Format tidak didukung: .' . ($ext ?: '(tanpa ekstensi)')
                . ' — gunakan berkas .csv, .txt, atau .xlsx.');
        }

        try {
            $rows = $ext === 'xlsx'
                ? Xlsx::read($file->getRealPath())
                : $this->readDelimitedRows(file_get_contents($file->getRealPath()));
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca berkas: ' . $e->getMessage());
        }

        if ($rows === []) {
            return back()->with('error', 'Berkas kosong — tidak ada baris untuk diimpor.');
        }

        $header = array_shift($rows);
        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $header);
        $idx = fn ($name) => array_search(mb_strtolower($name), $header, true);

        $iNama  = $idx('nama');
        $iEmail = $idx('email');
        $iPass  = $idx('password');
        $iRole  = $idx('role');

        if ($iNama === false || $iEmail === false || $iPass === false || $iRole === false) {
            $seen = array_values(array_filter($header, fn ($h) => $h !== ''));
            $missing = array_filter(['Nama', 'Email', 'Password', 'Role'],
                fn ($want) => array_search(mb_strtolower($want), $header, true) === false);

            return back()->with('error',
                'Header tidak valid. Ditemukan: [' . ($seen ? implode(', ', $seen) : 'tidak ada kolom terbaca') . ']'
                . '. Kolom wajib yang belum ada: [' . implode(', ', $missing) . '].'
                . ' Pemisah kolom harus koma (,) atau titik koma (;).');
        }

        $validRoles = Role::pluck('name')->all();
        $imported = 0;
        $skipped = 0;
        $issues = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $offset => $row) {
                $rowNum = $offset + 2;
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) continue;

                $name = trim((string) ($row[$iNama] ?? ''));
                $email = trim((string) ($row[$iEmail] ?? ''));
                $password = trim((string) ($row[$iPass] ?? ''));
                $role = mb_strtolower(trim((string) ($row[$iRole] ?? '')));
                $role = self::ROLE_ALIASES[$role] ?? $role;

                if ($name === '' || $email === '' || $password === '') {
                    $skipped++;
                    $issues[] = "Baris {$rowNum}: Nama/Email/Password wajib terisi.";
                    continue;
                }
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    $issues[] = "Baris {$rowNum}: email '{$email}' tidak valid.";
                    continue;
                }
                if (! in_array($role, $validRoles, true)) {
                    $skipped++;
                    $issues[] = "Baris {$rowNum}: role '{$role}' tidak dikenal. Role tersedia: " . implode(', ', $validRoles) . '.';
                    continue;
                }
                if (User::where('email', $email)->exists()) {
                    $skipped++;
                    $issues[] = "Baris {$rowNum}: email '{$email}' sudah terdaftar.";
                    continue;
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);
                $user->syncRoles([$role]);
                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        $msg = "Import selesai — {$imported} pengguna ditambahkan" . ($skipped ? ", {$skipped} dilewati" : "") . ".";
        if ($issues !== []) {
            $msg .= "\n" . implode("\n", array_slice($issues, 0, 10));
            if (count($issues) > 10) {
                $msg .= "\n... dan " . (count($issues) - 10) . " baris bermasalah lainnya.";
            }
        }

        return back()->with($imported > 0 ? 'success' : 'error', $msg);
    }

    /**
     * Baca berkas CSV/TSV mentah menjadi array baris.
     *
     * Menangani dua kerusakan khas ekspor Excel: BOM (UTF-8 maupun UTF-16)
     * dan pemisah titik koma (locale Windows yang list separator-nya koma-argumen).
     */
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
            if (trim($line) === '') continue;
            $rows[] = str_getcsv($line, $delimiter, '"', '');
        }

        return $rows;
    }

    /** Restrict account mutations to management roles. */
    private function authorizeManagement(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::MANAGER_ROLES),
            403,
            'Anda tidak memiliki izin mengelola pengguna.',
        );
    }

    /** Enforce one account-to-one student identity relationship. */
    private function validateStudentLink(?string $role, ?int $studentId, ?int $currentUserId = null): void
    {
        if (($role === 'siswa') !== ($studentId !== null)) {
            throw ValidationException::withMessages([
                'student_id' => $role === 'siswa'
                    ? 'Akun siswa wajib terhubung ke identitas siswa.'
                    : 'Akun guru harus tanpa identitas siswa.',
            ]);
        }

        if ($studentId === null) {
            return;
        }

        $linked = Student::whereKey($studentId)
            ->where(function ($query) use ($currentUserId) {
                $query->whereNull('user_id');
                if ($currentUserId !== null) {
                    $query->orWhere('user_id', $currentUserId);
                }
            })
            ->exists();

        if (! $linked) {
            throw ValidationException::withMessages([
                'student_id' => 'Identitas siswa sudah terhubung ke akun lain.',
            ]);
        }
    }
}
