<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Xlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    /** Domain email untuk akun yang dibuat otomatis (siswa / guru). */
    private const AUTO_EMAIL_DOMAIN = 'smpbn666.sch.id';

    /**
     * Display user accounts with Guru/Siswa filters.
     */
    public function index()
    {
        $this->authorizeManagement();

        $type = request('type');
        // Jabatan hanya bermakna saat tipe = guru; jangan biarkan parameter lama
        // di URL menyaring daftar tanpa terlihat (dropdown-nya tersembunyi).
        $jabatan = $type === 'guru' ? request('jabatan') : null;
        $q = request('q');
        $deletionRequested = request()->boolean('deletion_requested');
        $pendingDeletionCount = User::where('is_active', true)
            ->whereNotNull('deletion_requested_at')
            ->count();
        $studentsWithoutAccounts = Student::whereNull('user_id')->where('status', 'active')->count();
        $teachersWithoutAccounts = Teacher::whereNull('user_id')->where('status', 'active')->count();

        // Untuk kartu reset password massal (per kelas / per tingkatan).
        $classes = SchoolClass::with('academicYear')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level', 'academic_year_id']);
        $gradeOptions = SchoolClass::query()
            ->whereIn('grade_level', ['VII', 'VIII', 'IX'])
            ->distinct()
            ->pluck('grade_level')
            ->sortBy(fn ($grade) => ['VII' => 1, 'VIII' => 2, 'IX' => 3][$grade] ?? 99)
            ->values();

        $roles = Role::whereIn('name', self::STAFF_ROLES)->orderBy('name')->pluck('name');

        $sort = request()->string('sort')->toString();
        $order = strtolower(request()->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'name', 'email', 'role', 'is_active', 'created_at'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'name';

        $users = User::with(['roles', 'student.currentEnrollment.schoolClass', 'teacher'])
            ->when($q, fn ($query) => $query->where(function ($where) use ($q) {
                $where->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhereHas('student', fn ($student) => $student
                        ->where('full_name', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%"))
                    ->orWhereHas('teacher', fn ($teacher) => $teacher
                        ->where('full_name', 'like', "%{$q}%")
                        ->orWhere('nip', 'like', "%{$q}%")
                        ->orWhere('mata_pelajaran', 'like', "%{$q}%")
                        ->orWhere('jabatan', 'like', "%{$q}%"));
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
            ->when($deletionRequested, fn ($query) => $query
                ->where('is_active', true)
                ->whereNotNull('deletion_requested_at'))
            ->when($sort === 'role', fn ($query) => $query->orderBy(
                Role::query()
                    ->select('roles.name')
                    ->join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', User::class)
                    ->orderBy('roles.name')
                    ->limit(1),
                $order,
            ))
            ->when($sort !== 'role', fn ($query) => $query->orderBy($sort, $order))
            ->orderBy('users.id')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact(
            'users',
            'roles',
            'type',
            'jabatan',
            'q',
            'sort',
            'order',
            'deletionRequested',
            'pendingDeletionCount',
            'studentsWithoutAccounts',
            'teachersWithoutAccounts',
            'classes',
            'gradeOptions',
        ));
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
        $teachers = Teacher::whereNull('user_id')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'nip', 'mata_pelajaran']);

        return view('users.create', compact('roles', 'students', 'teachers'));
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
            'teacher_id' => 'nullable|integer|exists:teachers,id',
        ]);

        $role = $validated['role'];
        $studentId = $validated['student_id'] ?? null;
        $teacherId = $validated['teacher_id'] ?? null;
        $this->validateStudentLink($role, $studentId);
        $this->validateTeacherLink($role, $teacherId);

        $user = DB::transaction(function () use ($validated, $role, $studentId, $teacherId) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->syncRoles([$role]);

            if ($studentId !== null) {
                Student::whereKey($studentId)->update(['user_id' => $user->id]);
            }

            if ($teacherId !== null) {
                Teacher::whereKey($teacherId)->update(['user_id' => $user->id]);
            }

            return $user;
        });

        ActivityLog::record(
            'user.create',
            'Menambahkan pengguna '.$user->name,
            [
                'role' => $role,
                'email' => $user->email,
                'student_id' => $studentId,
                'teacher_id' => $teacherId,
                'accounts' => [[
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $role,
                    'identity' => $studentId ? Student::whereKey($studentId)->value('nis') : Teacher::whereKey($teacherId)->value('nip'),
                ]],
            ],
            $user,
        );

        return redirect()->route('users.index')
            ->with('success', 'User '.$user->name.' berhasil dibuat.');
    }

    /**
     * Display one user account and its linked student identity.
     */
    public function show(User $user)
    {
        $this->authorizeManagement();

        $user->load(['roles', 'student.currentEnrollment.schoolClass', 'student.currentEnrollment.academicYear', 'teacher']);

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
        $teachers = Teacher::where(function ($query) use ($user) {
            $query->whereNull('user_id')->orWhere('user_id', $user->id);
        })
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'nip', 'mata_pelajaran']);
        $user->load('roles');

        return view('users.edit', compact('user', 'roles', 'students', 'teachers'));
    }

    /**
     * Update account data, profile photo, role, and optional student identity link.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'nip' => 'nullable|string|max:40',
            'role' => 'sometimes|required|exists:roles,name',
            'student_id' => 'nullable|integer|exists:students,id',
            'teacher_id' => 'nullable|integer|exists:teachers,id',
            'avatar' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:15360',
            'remove_avatar' => 'nullable|boolean',
        ], [
            'avatar.max' => 'Ukuran foto profil maksimal 15MB.',
            'avatar.image' => 'Foto profil harus berupa gambar.',
            'avatar.mimes' => 'Foto profil harus berformat PNG, JPG, JPEG, atau WEBP.',
        ]);

        $avatar = $validated['avatar'] ?? null;
        $removeAvatar = (bool) ($validated['remove_avatar'] ?? false);
        unset($validated['avatar'], $validated['remove_avatar']);

        if ($avatar instanceof UploadedFile) {
            $validated['avatar_path'] = $avatar->store('avatars', 'public');
        } elseif ($removeAvatar) {
            $validated['avatar_path'] = null;
        }

        $oldAvatar = $user->avatar_path;

        $role = $validated['role'] ?? $user->roles->first()?->name;
        $studentId = array_key_exists('student_id', $validated)
            ? $validated['student_id']
            : ($role === 'siswa' ? $user->student?->id : null);
        $teacherId = array_key_exists('teacher_id', $validated)
            ? $validated['teacher_id']
            : (in_array($role, ['guru', 'wali_kelas'], true) ? $user->teacher?->id : null);
        $this->validateStudentLink($role, $studentId, $user->id);
        $this->validateTeacherLink($role, $teacherId, $user->id);

        $previousRole = $user->roles->first()?->name;
        $changes = [];
        if (array_key_exists('name', $validated) && $validated['name'] !== $user->name) {
            $changes[] = 'nama';
        }
        if (array_key_exists('email', $validated) && $validated['email'] !== $user->email) {
            $changes[] = 'email';
        }
        if (array_key_exists('nip', $validated) && $validated['nip'] !== $user->nip) {
            $changes[] = 'NIP';
        }
        if (! empty($validated['password'])) {
            $changes[] = 'password';
        }
        if (array_key_exists('avatar_path', $validated)) {
            $changes[] = 'foto profil';
        }
        if ($role !== $previousRole) {
            $changes[] = 'role';
        }
        if ($studentId !== $user->student?->id) {
            $changes[] = 'identitas siswa';
        }
        if ($teacherId !== $user->teacher?->id) {
            $changes[] = 'identitas guru';
        }

        DB::transaction(function () use ($validated, $user, $role, $studentId, $teacherId) {
            $userData = collect($validated)->only(['name', 'email', 'password', 'nip', 'avatar_path'])->all();
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }
            $user->update($userData);
            $user->syncRoles([$role]);

            Student::where('user_id', $user->id)->update(['user_id' => null]);
            if ($studentId !== null) {
                Student::whereKey($studentId)->update(['user_id' => $user->id]);
            }

            Teacher::where('user_id', $user->id)->update(['user_id' => null]);
            if ($teacherId !== null) {
                Teacher::whereKey($teacherId)->update(['user_id' => $user->id]);
            }

            if (array_key_exists('avatar_path', $userData)) {
                // Foto akun dipakai bersama oleh data siswa/guru yang tertaut.
                Student::where('user_id', $user->id)->update(['avatar_path' => $userData['avatar_path']]);
                Teacher::where('user_id', $user->id)->update(['avatar_path' => $userData['avatar_path']]);
            }
        });

        if (array_key_exists('avatar_path', $validated)
            && $oldAvatar
            && $oldAvatar !== $validated['avatar_path']
            && Storage::disk('public')->exists($oldAvatar)) {
            Storage::disk('public')->delete($oldAvatar);
        }

        ActivityLog::record(
            'user.update',
            'Memperbarui pengguna '.$user->name.($changes !== [] ? ' ('.implode(', ', $changes).')' : ''),
            [
                'changes' => $changes,
                'role' => $role,
                'password_changed' => ! empty($validated['password']),
                'avatar_changed' => array_key_exists('avatar_path', $validated),
            ],
            $user,
        );

        return redirect()->route('users.edit', $user)
            ->with('success', 'Data pengguna '.$user->name.' berhasil diperbarui.');
    }

    /**
     * Deactivate a user account while retaining its student identity.
     */
    public function destroy(User $user)
    {
        $this->authorizeManagement();

        if ($user->is(request()->user())) {
            return redirect()->route('users.index')
                ->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        $this->deactivateAccount($user, request()->user());

        ActivityLog::record(
            'user.deactivate',
            'Menonaktifkan akun '.$user->name,
            ['email' => $user->email],
            $user,
        );

        return redirect()->route('users.index')
            ->with('success', 'Akun dinonaktifkan. Data dan identitas siswa tetap tersimpan.');
    }

    /** Approve a pending request by deactivating the account without deleting it. */
    public function approveDeletionRequest(User $user): RedirectResponse
    {
        $this->authorizeManagement();

        if ($user->is(request()->user())) {
            return redirect()->route('users.index')
                ->with('error', 'Permintaan akun sendiri harus diproses oleh pengelola lain.');
        }

        if (! $user->deletion_requested_at) {
            return redirect()->route('users.index')
                ->with('error', 'Akun ini tidak memiliki permintaan penghapusan yang menunggu.');
        }

        $this->deactivateAccount($user, request()->user());

        return redirect()->route('users.index')
            ->with('success', 'Permintaan disetujui. Akun dinonaktifkan dan seluruh data tetap tersimpan.');
    }

    /** Reactivate an account that has not been soft-deleted. */
    public function activate(User $user): RedirectResponse
    {
        $this->authorizeManagement();

        $user->forceFill([
            'is_active' => true,
            'deletion_requested_at' => null,
            'deletion_approved_at' => null,
            'deletion_approved_by' => null,
        ])->save();

        return redirect()->route('users.index')
            ->with('success', 'Akun '.$user->name.' berhasil diaktifkan kembali.');
    }

    public function rejectDeletionRequest(User $user): RedirectResponse
    {
        $this->authorizeManagement();

        if (! $user->deletion_requested_at) {
            return redirect()->route('users.index')
                ->with('error', 'Akun ini tidak memiliki permintaan penghapusan yang menunggu.');
        }

        $user->deletion_requested_at = null;
        $user->save();

        return redirect()->route('users.index')
            ->with('success', 'Permintaan penghapusan akun ditolak.');
    }

    // ────────────────────────────────────────────────
    // Generate akun otomatis (siswa / guru)
    // ────────────────────────────────────────────────

    /**
     * Buat akun untuk siswa / guru aktif yang belum punya akun.
     *
     * Email dibuat dari NIS (siswa) atau NIP (guru); bila kosong memakai ID.
     * Password dibuat acak dan hanya dikembalikan sekali lewat session flash —
     * tidak pernah disimpan dalam bentuk teks biasa.
     */
    public function generateAccounts(Request $request): RedirectResponse
    {
        $this->authorizeManagement();

        // Generate ratusan akun = ratusan kali hashing password. Naikkan batas waktu
        // request agar tidak fatal error 30 detik (PHP default).
        set_time_limit(300);

        $validated = $request->validate([
            'type' => ['required', 'in:siswa,guru'],
        ]);

        $type = $validated['type'];
        $roleName = $type === 'siswa' ? 'siswa' : 'guru';
        $generated = [];

        // Hashing password adalah biaya terbesar saat generate massal; helper
        // hashForBulkAccount() memakai cost lebih ringan (lihat catatan di sana).
        DB::transaction(function () use ($type, $roleName, &$generated) {
            if ($type === 'siswa') {
                $students = Student::whereNull('user_id')
                    ->where('status', 'active')
                    ->with('currentEnrollment.schoolClass')
                    ->orderBy('full_name')
                    ->get();

                foreach ($students as $student) {
                    $password = $this->makeSimplePassword();
                    $email = $this->makeAutoEmail($student->nis ?: 'S'.$student->id);

                    $user = User::create([
                        'name' => $student->full_name,
                        'email' => $email,
                        'password' => $this->hashForBulkAccount($password),
                    ]);
                    $user->syncRoles([$roleName]);
                    $student->update(['user_id' => $user->id]);

                    $generated[] = [
                        'name' => $student->full_name,
                        'class_name' => $student->currentEnrollment?->schoolClass?->name ?? '',
                        'email' => $email,
                        'password' => $password,
                        'role' => $roleName,
                        'identity' => $student->nis,
                    ];
                }

                return;
            }

            $teachers = Teacher::whereNull('user_id')
                ->where('status', 'active')
                ->orderBy('full_name')
                ->get();

            foreach ($teachers as $teacher) {
                $password = $this->makeSimplePassword();

                // Email guru memakai nama (tanpa gelar setelah koma), huruf kecil.
                // Bila nama bentrok, makeAutoEmail menambahkan akhiran .2, .3, dst.
                $email = $this->makeAutoEmail(Str::before($teacher->full_name, ','));

                // Jabatan struktural tertentu otomatis memakai role yang sesuai;
                // sisanya tetap guru dan bisa diubah manual di halaman pengguna.
                $teacherRole = $this->resolveTeacherRole($teacher);

                $user = User::create([
                    'name' => $teacher->full_name,
                    'email' => $email,
                    'password' => $this->hashForBulkAccount($password),
                    'nip' => $teacher->nip,
                ]);
                $user->syncRoles([$teacherRole]);
                $teacher->update(['user_id' => $user->id]);

                $generated[] = [
                    'name' => $teacher->full_name,
                    'class_name' => '',
                    'email' => $email,
                    'password' => $password,
                    'role' => $teacherRole,
                    'identity' => $teacher->nip ?: '-',
                ];
            }
        });

        if ($generated === []) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak ada data '.$type.' aktif yang belum memiliki akun.');
        }

        ActivityLog::record(
            'user.generate',
            count($generated).' akun '.$type.' berhasil dibuat otomatis',
            [
                'type' => $type,
                'total' => count($generated),
                // Password sengaja tidak pernah dicatat di log.
                'accounts' => array_map(fn ($account) => [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'role' => $account['role'],
                    'identity' => $account['identity'],
                ], $generated),
            ],
        );

        // Simpan juga di sesi (bukan flash) supaya tombol Unduh CSV tetap bekerja
        // setelah halaman redirect tampil, dan daftar bisa diunduh ulang selama sesi.
        session()->put('generated_credentials', $generated);
        session()->put('generated_credentials_label', 'semua '.$type);

        return redirect()->route('users.index')
            ->with('success', count($generated).' akun '.$type.' berhasil dibuat otomatis. Simpan atau unduh daftar password sekarang — password hanya ditampilkan sekali.')
            ->with('generated_accounts', $generated);
    }

    /**
     * Reset password massal: semua akun, per kelas, atau per angkatan.
     *
     * Password baru dibuat acak, ditampilkan sekali lewat session flash, dan
     * tidak pernah disimpan dalam bentuk teks biasa maupun ditulis ke log.
     */
    public function resetPasswords(Request $request): RedirectResponse
    {
        $this->authorizeManagement();

        // Hashing per akun — beri waktu lebih untuk rombel/tingkatan besar.
        set_time_limit(300);

        $validated = $request->validate([
            'type' => ['required', 'in:siswa,guru,all'],
            'scope' => ['required', 'in:all,class,grade'],
            'class_id' => ['nullable', 'required_if:scope,class', 'integer', 'exists:classes,id'],
            'grade_level' => ['nullable', 'required_if:scope,grade', 'in:VII,VIII,IX'],
        ], [
            'class_id.required_if' => 'Pilih kelas yang akan direset passwordnya.',
            'grade_level.required_if' => 'Pilih tingkatan yang akan direset passwordnya.',
        ]);

        $type = $validated['type'];
        $scope = $validated['scope'];

        // Kelas/tingkatan hanya bermakna untuk akun siswa; tipe guru maupun
        // "semua pengguna" selalu mencakup semua akun aktif.
        if ($type !== 'siswa') {
            $scope = 'all';
        }

        $query = User::query()
            ->where('is_active', true)
            ->when($type !== 'all', fn ($query) => $query->whereHas(
                'roles',
                fn ($role) => $role->where('name', $type),
            ))
            // Akun yang sedang dipakai tidak ikut direset agar admin tidak
            // terkunci dari akunnya sendiri.
            ->when($type === 'all', fn ($query) => $query->whereKeyNot($request->user()->getKey()))
            ->with([
                'roles:id,name',
                'student:id,user_id,nis',
                'student.currentEnrollment.schoolClass',
                'teacher:id,user_id,nip',
            ]);

        if ($scope === 'class') {
            $query->whereHas('student.enrollments', fn ($enrollment) => $enrollment
                ->where('class_id', (int) $validated['class_id'])
                ->where('status', 'active'));
        } elseif ($scope === 'grade') {
            $query->whereHas('student.currentEnrollment.schoolClass', fn ($class) => $class
                ->where('grade_level', $validated['grade_level']));
        }

        $users = $query->orderBy('name')->get();

        $className = $scope === 'class'
            ? (SchoolClass::whereKey($validated['class_id'])->value('name') ?? '-')
            : null;

        // Label ringkas untuk pesan & log aktivitas.
        $targetLabel = $type === 'all' ? 'semua pengguna' : 'akun '.$type;

        $scopeLabel = match ($scope) {
            'class' => 'kelas '.$className,
            'grade' => 'tingkatan '.$validated['grade_level'],
            default => $type === 'all' ? 'semua pengguna' : 'semua akun '.$type,
        };

        // Label untuk judul berkas CSV (mis. "password XI A").
        $csvLabel = match ($scope) {
            'class' => $className,
            'grade' => 'tingkatan '.$validated['grade_level'],
            default => $type === 'all' ? 'semua pengguna' : 'semua '.$type,
        };

        if ($users->isEmpty()) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak ada '.$targetLabel.' aktif pada '.$scopeLabel.' yang bisa direset.');
        }

        $generated = [];

        DB::transaction(function () use ($users, $type, &$generated) {
            foreach ($users as $user) {
                $password = $this->makeSimplePassword();
                $user->forceFill(['password' => $this->hashForBulkAccount($password)])->save();
                // Password baru membatalkan sesi API lama.
                $user->tokens()->delete();

                $generated[] = [
                    'name' => $user->name,
                    'class_name' => $user->student?->currentEnrollment?->schoolClass?->name ?? '',
                    'email' => $user->email,
                    'password' => $password,
                    // Untuk "semua pengguna" tampilkan role asli tiap akun.
                    'role' => $type === 'all' ? ($user->roles->first()?->name ?? '-') : $type,
                    'identity' => $user->student?->nis ?: ($user->nip ?: '-'),
                ];
            }
        });

        ActivityLog::record(
            'user.password_reset',
            'Reset password '.count($generated).' '.$targetLabel.' ('.$scopeLabel.')',
            [
                'type' => $type,
                'scope' => $scope,
                'scope_label' => $scopeLabel,
                'total' => count($generated),
                'accounts' => array_map(fn ($account) => [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'role' => $account['role'],
                    'identity' => $account['identity'],
                ], $generated),
            ],
        );

        // Simpan di sesi agar tombol Unduh CSV tetap berfungsi setelah redirect.
        session()->put('generated_credentials', $generated);
        session()->put('generated_credentials_label', $csvLabel);

        return redirect()->route('users.index')
            ->with('success', count($generated).' password '.$targetLabel.' ('.$scopeLabel.') berhasil direset. Salin atau unduh sekarang — password hanya ditampilkan sekali.')
            ->with('generated_accounts', $generated);
    }

    /** Reset password satu akun. */
    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorizeManagement();

        if ($user->is(request()->user())) {
            return back()->with('error', 'Password akun sendiri tidak dapat direset dari halaman ini. Gunakan menu Profil Saya.');
        }

        $password = $this->makeSimplePassword();
        $user->forceFill(['password' => $this->hashForBulkAccount($password)])->save();
        $user->tokens()->delete();

        $user->loadMissing('student.currentEnrollment.schoolClass');

        ActivityLog::record(
            'user.password_reset',
            'Reset password akun '.$user->name,
            [
                'total' => 1,
                'accounts' => [[
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name,
                    'identity' => $user->student?->nis ?: ($user->nip ?: '-'),
                ]],
            ],
            $user,
        );

        $credentials = [[
            'name' => $user->name,
            'class_name' => $user->student?->currentEnrollment?->schoolClass?->name ?? '',
            'email' => $user->email,
            'password' => $password,
            'role' => $user->roles->first()?->name ?? '-',
            'identity' => $user->student?->nis ?: ($user->nip ?: '-'),
        ]];

        session()->put('generated_credentials', $credentials);
        session()->put('generated_credentials_label', $user->name);

        return redirect()->route('users.index')
            ->with('success', 'Password '.$user->name.' berhasil direset. Salin sekarang — password hanya ditampilkan sekali.')
            ->with('generated_accounts', $credentials);
    }

    /** Hapus daftar kredensial dari sesi (password tidak bisa dipulihkan lagi). */
    public function clearGeneratedAccounts(): RedirectResponse
    {
        $this->authorizeManagement();

        session()->forget(['generated_accounts', 'generated_credentials', 'generated_credentials_label']);

        return redirect()->route('users.index')
            ->with('success', 'Daftar kredensial dihapus dari sesi ini.');
    }

    /** Unduh kredensial akun yang baru dibuat / direset sebagai CSV. */
    public function downloadGeneratedAccounts(Request $request)
    {
        $this->authorizeManagement();

        // Baca dari sesi (bukan flash) agar unduhan tetap bekerja setelah halaman
        // redirect tampil, dan bisa diunduh ulang selama sesi masih aktif.
        $accounts = $request->session()->get(
            'generated_credentials',
            $request->session()->get('generated_accounts', []),
        );

        if ($accounts === []) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak ada daftar akun baru untuk diunduh. Generate atau reset password dulu.');
        }

        // Judul berkas mengikuti cakupan reset, mis. "password XI A.csv".
        $label = (string) $request->session()->get('generated_credentials_label', 'akun-baru');
        $label = trim(str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '-', $label)) ?: 'akun-baru';
        $filename = 'password '.$label.'.csv';

        return response()->streamDownload(function () use ($accounts) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Nama', 'Kelas', 'Email', 'Password']);

            foreach ($accounts as $account) {
                fputcsv($out, [
                    $account['name'],
                    $account['class_name'] ?? '',
                    $account['email'],
                    $account['password'],
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Role akun dari jabatan guru.
     *
     * Hanya jabatan struktural yang dipetakan otomatis: Kepala Sekolah,
     * Kesiswaan, dan BK/bimbingan konseling. Jabatan lain (Wks. Kurikulum,
     * Pembina OSIS, dst.) tetap guru. Untuk data lama, kolom jabatan bisa
     * masih kosong sehingga mata pelajaran dipakai sebagai cadangan.
     */
    private function resolveTeacherRole(Teacher $teacher): string
    {
        $position = mb_strtolower(trim((string) ($teacher->jabatan ?: $teacher->mata_pelajaran)));

        return match (true) {
            str_contains($position, 'kepala sekolah') => 'kepala_sekolah',
            str_contains($position, 'kesiswaan') => 'kesiswaan',
            (bool) preg_match('/\b(bk|bimbingan)\b/', $position) => 'bk',
            default => 'guru',
        };
    }

    /**
     * Email unik untuk akun otomatis.
     *
     * Siswa memakai NIS langsung (mis. 24257001@smpbn666.sch.id), guru memakai
     * nama tanpa gelar (mis. budisantoso@smpbn666.sch.id). Hanya huruf/angka
     * yang dipakai, lalu diberi akhiran angka bila sudah terpakai (termasuk
     * akun yang sudah di-soft-delete).
     */
    private function makeAutoEmail(string $identifier): string
    {
        $local = Str::lower(preg_replace('/[^A-Za-z0-9]+/', '', $identifier));
        $local = $local !== '' ? $local : Str::lower(Str::random(8));
        $base = $local.'@'.self::AUTO_EMAIL_DOMAIN;

        $email = $base;
        $suffix = 2;

        while (User::withTrashed()->where('email', $email)->exists()) {
            $email = $local.'.'.$suffix.'@'.self::AUTO_EMAIL_DOMAIN;
            $suffix++;
        }

        return $email;
    }

    /**
     * Password sederhana untuk akun siswa/guru: 6 digit angka.
     *
     * Sengaja tanpa huruf/simbol agar mudah dibacakan di kelas. Tidak diawali
     * angka 0 supaya tidak terbaca sebagai angka lain saat disalin ke Excel.
     */
    private function makeSimplePassword(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Hash password untuk pembuatan akun massal (generate / import).
     *
     * Bcrypt default (cost 12) butuh ratusan milidetik per akun, sehingga 150+ akun
     * bisa melewati batas waktu request. Cost diturunkan sementara ke 8: password
     * hasil generate acak dan hanya tampil sekali, dan Laravel otomatis melakukan
     * rehash ke cost normal saat pengguna login pertama (hashing.rehash_on_login).
     * Cost tidak pernah dinaikkan di atas konfigurasi (mis. saat test memakai cost 4).
     */
    private function hashForBulkAccount(string $password): string
    {
        if (config('hashing.driver', 'bcrypt') !== 'bcrypt') {
            return Hash::make($password);
        }

        $originalRounds = (int) config('hashing.bcrypt.rounds', 12);
        Hash::setRounds(min($originalRounds, 8));

        try {
            return Hash::make($password);
        } finally {
            Hash::setRounds($originalRounds);
        }
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
        $this->authorizeManagement();

        $path = public_path('template-pengguna.xlsx');

        if (is_file($path)) {
            return response()->download($path, 'template-pengguna.xlsx');
        }

        $headers = ['Nama', 'Email', 'Password', 'Role'];
        $example = ['Budi Santoso', 'budi@guru.com', 'rahasia123', 'guru'];

        return response()->streamDownload(function () use ($headers, $example) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers);
            fputcsv($out, $example);
            fclose($out);
        }, 'template-pengguna.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Export pengguna ke XLSX dengan styling header */
    public function export()
    {
        $this->authorizeManagement();

        $users = User::with(['roles', 'student.currentEnrollment.schoolClass', 'teacher'])
            ->orderBy('name')
            ->limit(5000)
            ->get();

        $rows = [
            ['Nama', 'Email', 'Role', 'Identitas Siswa', 'Kelas', 'NIP Guru', 'Mata Pelajaran'],
        ];

        foreach ($users as $u) {
            $role = $u->roles->first()?->name ?? '-';
            $studentInfo = $u->student ? $u->student->nisn.' - '.$u->student->full_name : '-';
            $kelas = $u->student?->currentEnrollment?->schoolClass?->name ?? '-';
            $rows[] = [
                $u->name,
                $u->email,
                $role,
                $studentInfo,
                $kelas,
                $u->teacher?->nip ?? '-',
                $u->teacher?->mata_pelajaran ?? '-',
            ];
        }

        $path = Xlsx::write($rows, 'Daftar Pengguna', true);
        $filename = 'pengguna-'.now()->format('Ymd-His').'.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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

        // Import massal juga melakukan hashing per akun — beri waktu lebih.
        set_time_limit(300);

        $request->validate([
            'file' => 'required|file|max:4096',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['csv', 'txt', 'xlsx'];

        if (! in_array($ext, $allowed, true)) {
            $message = 'Format tidak didukung: .'.($ext ?: '(tanpa ekstensi)')
                .' — gunakan berkas .csv, .txt, atau .xlsx.';
            $this->logImportFailure($message, $fileName);

            return back()->with('error', $message);
        }

        try {
            $rows = $ext === 'xlsx'
                ? Xlsx::read($file->getRealPath())
                : $this->readDelimitedRows(file_get_contents($file->getRealPath()));
        } catch (\Throwable $e) {
            $this->logImportFailure('Gagal membaca berkas: '.$e->getMessage(), $fileName);

            return back()->with('error', 'Gagal membaca berkas: '.$e->getMessage());
        }

        if ($rows === []) {
            $this->logImportFailure('Berkas kosong — tidak ada baris untuk diimpor.', $fileName);

            return back()->with('error', 'Berkas kosong — tidak ada baris untuk diimpor.');
        }

        $header = array_shift($rows);
        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $header);
        $idx = fn ($name) => array_search(mb_strtolower($name), $header, true);

        $iNama = $idx('nama');
        $iEmail = $idx('email');
        $iPass = $idx('password');
        $iRole = $idx('role');

        if ($iNama === false || $iEmail === false || $iPass === false || $iRole === false) {
            $seen = array_values(array_filter($header, fn ($h) => $h !== ''));
            $missing = array_filter(['Nama', 'Email', 'Password', 'Role'],
                fn ($want) => array_search(mb_strtolower($want), $header, true) === false);

            $message = 'Header tidak valid. Ditemukan: ['.($seen ? implode(', ', $seen) : 'tidak ada kolom terbaca').']'
                .'. Kolom wajib yang belum ada: ['.implode(', ', $missing).'].'
                .' Pemisah kolom harus koma (,) atau titik koma (;).';
            $this->logImportFailure($message, $fileName);

            return back()->with('error', $message);
        }

        $validRoles = Role::pluck('name')->all();
        $imported = 0;
        $skipped = 0;
        $issues = [];
        $createdAccounts = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $offset => $row) {
                $rowNum = $offset + 2;
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

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
                    $issues[] = "Baris {$rowNum}: role '{$role}' tidak dikenal. Role tersedia: ".implode(', ', $validRoles).'.';

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
                    'password' => $this->hashForBulkAccount($password),
                ]);
                $user->syncRoles([$role]);
                $imported++;
                $createdAccounts[] = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $role,
                    'identity' => '-',
                ];
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logImportFailure('Import gagal: '.$e->getMessage(), $fileName);

            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        $msg = "Import selesai — {$imported} pengguna ditambahkan".($skipped ? ", {$skipped} dilewati" : '').'.';
        if ($issues !== []) {
            $msg .= "\n".implode("\n", array_slice($issues, 0, 10));
            if (count($issues) > 10) {
                $msg .= "\n... dan ".(count($issues) - 10).' baris bermasalah lainnya.';
            }
        }

        ActivityLog::record(
            'user.import',
            "Import pengguna: {$imported} ditambahkan, {$skipped} dilewati ({$fileName})",
            [
                'status' => 'ok',
                'file' => $fileName,
                'imported' => $imported,
                'skipped' => $skipped,
                'issues_total' => count($issues),
                'issues' => array_slice($issues, 0, 500),
                'accounts' => array_slice($createdAccounts, 0, 500),
            ],
        );

        return back()->with($imported > 0 ? 'success' : 'error', $msg);
    }

    /** Catat percobaan import yang gagal total agar bisa ditelusuri admin. */
    private function logImportFailure(string $message, ?string $fileName = null): void
    {
        ActivityLog::record('user.import', 'Import pengguna gagal: '.$message, [
            'status' => 'failed',
            'file' => $fileName,
            'reason' => $message,
        ]);
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
            if (trim($line) === '') {
                continue;
            }
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

    /** Persist account deactivation and revoke every Sanctum token atomically. */
    private function deactivateAccount(User $user, User $manager): void
    {
        DB::transaction(function () use ($user, $manager) {
            $user->forceFill([
                'is_active' => false,
                'deletion_requested_at' => null,
                'deletion_approved_at' => now(),
                'deletion_approved_by' => $manager->id,
            ])->save();

            $user->tokens()->delete();
        });
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

    /** Enforce one account-to-one teacher record and only for teaching roles. */
    private function validateTeacherLink(?string $role, ?int $teacherId, ?int $currentUserId = null): void
    {
        if ($teacherId === null) {
            return;
        }

        if (! in_array($role, ['guru', 'wali_kelas'], true)) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Data guru hanya dapat ditautkan ke akun ber-role guru atau wali kelas.',
            ]);
        }

        $linked = Teacher::whereKey($teacherId)
            ->where(function ($query) use ($currentUserId) {
                $query->whereNull('user_id');
                if ($currentUserId !== null) {
                    $query->orWhere('user_id', $currentUserId);
                }
            })
            ->exists();

        if (! $linked) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Data guru sudah tertaut ke akun lain.',
            ]);
        }
    }
}
