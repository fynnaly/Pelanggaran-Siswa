<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    private const MANAGER_ROLES = ['admin', 'bk', 'kesiswaan', 'kepala_sekolah'];

    use ScopesWaliKelas;

    /** Daftar role yang dianggap staf sekolah. User tanpa role apa pun = DITOLAK. */
    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    private function authorizeStaff(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::STAFF_ROLES),
            403,
            'Akses ditolak. Fitur ini hanya untuk staf sekolah.'
        );
    }

    private function authorizeManagement(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::MANAGER_ROLES),
            403,
            'Hanya admin / kesiswaan / kepala sekolah yang boleh mengubah data kelas.',
        );
    }

    /** Menampilkan daftar semua kelas */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $academicYears = AcademicYear::orderBy('start_date')->get();
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $selectedYearId = request('year_id') ?: $activeYearId;

        $sort = $request->string('sort')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'name', 'grade_level', 'angkatan', 'academic_year_id', 'status', 'students_count'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'id';

        $query = SchoolClass::with(['academicYear', 'homeroomTeacher.teacher', 'assistantTeacher.teacher'])->withCount('students')
            ->where('academic_year_id', $selectedYearId);

        // Scope wali kelas: null = role pengawas/penuh, array = wajib filter (bisa kosong = tidak ada kelas binaan)
        $waliScope = $this->waliKelasScope();
        if ($waliScope !== null) {
            $query->whereIn('id', $waliScope);
        }

        $classes = $query->orderBy($sort, $order)
            ->paginate(20)
            ->withQueryString();
        // Ambil data guru dari master Data Guru dan akun guru/wali kelas
        $teachers = $this->getAvailableHomeroomTeachers($classes);

        return view('classes.index', compact('classes', 'academicYears', 'selectedYearId', 'teachers', 'sort', 'order'));
    }

    /** Menampilkan detail satu kelas beserta daftarnya */
    public function show(SchoolClass $class)
    {
        $this->authorizeStaff();

        // Wali kelas hanya boleh membuka detail kelas binaannya sendiri
        $waliScope = $this->waliKelasScope();
        if ($waliScope !== null) {
            abort_unless(
                in_array($class->id, $waliScope, true),
                403,
                'Akses ditolak. Anda hanya dapat memantau kelas yang Anda bimbing sebagai wali kelas.'
            );
        }

        $class->load(['academicYear', 'homeroomTeacher.teacher', 'assistantTeacher.teacher']);
        $students = $class->students()
            ->with(['currentEnrollment', 'pointLedgers'])
            ->paginate(20);

        $zoneCounts = [];
        foreach (config('zones.zones', []) as $z) {
            $zoneCounts[$z['name']] = 0;
        }
        foreach ($students as $s) {
            $bal = $s->pointLedgers->sortBy('id')->last()->balance_after ?? 0;
            foreach (config('zones.zones', []) as $z) {
                $minOk = $z['min'] === null || $bal >= $z['min'];
                $maxOk = $z['max'] === null || $bal <= $z['max'];
                if ($minOk && $maxOk) {
                    $zoneCounts[$z['name']]++;
                    break;
                }
            }
        }

        return view('classes.show', compact('class', 'students', 'zoneCounts'));
    }

    /** Menampilkan form pembuatan kelas baru */
    public function create()
    {
        $this->authorizeManagement();

        $teachers = $this->getAvailableHomeroomTeachers();
        $users = $teachers;

        // Form kelas baru selalu memakai tahun ajaran yang sedang aktif.
        $activeYear = AcademicYear::where('is_active', true)->first();
        $hasPrerequisites = (bool) $activeYear;
        $prerequisites = [
            [
                'label' => 'Tahun Ajaran',
                'ready' => (bool) $activeYear,
                'url' => route('tahun-ajaran.create'),
                'action' => 'Buat Tahun Ajaran',
            ],
        ];

        return view('classes.create', compact('teachers', 'users', 'activeYear', 'hasPrerequisites', 'prerequisites'));
    }

    /** Menyimpan kelas baru ke database */
    public function store(Request $request)
    {
        $this->authorizeManagement();

        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        if (! $activeYearId) {
            return back()->withInput()->withErrors([
                'academic_year_id' => 'Belum ada tahun ajaran aktif. Aktifkan tahun ajaran terlebih dahulu.',
            ]);
        }

        $validated = $request->validate([
            'grade_level' => ['required', 'in:VII,VIII,IX'],
            'rombel' => ['required', 'string', 'max:2', 'regex:/^[A-Za-z]{1,2}$/'],
            'homeroom_teacher_id' => 'nullable',
            'assistant_teacher_id' => 'nullable',
            'status' => 'nullable|in:active,inactive',
        ]);
        $validated['status'] ??= 'active';
        $validated['academic_year_id'] = $activeYearId;

        $validated['homeroom_teacher_id'] = $this->resolveTeacherUserId($request->input('homeroom_teacher_id'));
        if ($validated['homeroom_teacher_id'] !== null) {
            $user = User::find($validated['homeroom_teacher_id']);
            if ($user && ! $user->hasRole('wali_kelas')) {
                $user->assignRole('wali_kelas');
            }
        }

        $validated['assistant_teacher_id'] = $this->resolveTeacherUserId($request->input('assistant_teacher_id'));

        // Nama kelas = Tingkatan + Rombel, mis. "VII A" atau "VIII B".
        $rombel = strtoupper(trim($validated['rombel']));
        $validated['name'] = $validated['grade_level'].' '.$rombel;
        unset($validated['rombel']);

        // Cegah duplikat nama kelas pada tahun ajaran yang sama
        $duplicate = SchoolClass::where('academic_year_id', $validated['academic_year_id'])
            ->where('name', $validated['name'])
            ->exists();
        if ($duplicate) {
            return back()->withInput()->withErrors([
                'rombel' => "Kelas {$validated['name']} sudah ada pada tahun ajaran ini. Gunakan rombel lain.",
            ]);
        }

        SchoolClass::create($validated);

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas '.$validated['name'].' berhasil ditambahkan.');
    }

    /** Menampilkan form edit kelas */
    public function edit(SchoolClass $class)
    {
        $this->authorizeManagement();

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $teachers = $this->getAvailableHomeroomTeachers(collect([$class]));
        $users = $teachers;

        return view('classes.edit', compact('class', 'academicYears', 'teachers', 'users'));
    }

    /** Memperbarui data kelas */
    public function update(Request $request, SchoolClass $class)
    {
        $this->authorizeManagement();

        // 1. Kasus quick update dari tabel (misalnya hanya mengganti Wali Kelas)
        if (! $request->has('grade_level') && ! $request->has('rombel') && $request->has('homeroom_teacher_id')) {
            $teacherUserId = $this->resolveTeacherUserId($request->input('homeroom_teacher_id'));

            if ($teacherUserId !== null) {
                $user = User::find($teacherUserId);
                if ($user && ! $user->hasRole('wali_kelas')) {
                    $user->assignRole('wali_kelas');
                }
            }

            $class->update(['homeroom_teacher_id' => $teacherUserId]);

            $teacherName = $class->fresh()->homeroom_teacher_name ?? 'Belum ditentukan';

            return redirect()
                ->route('classes.index')
                ->with('success', 'Wali kelas untuk '.$class->name.' berhasil diperbarui menjadi '.$teacherName.'.');
        }

        // 2. Kasus form edit kelas lengkap
        $academicYearId = $request->input('academic_year_id', $class->academic_year_id);

        $validated = $request->validate([
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'grade_level' => ['required', 'in:VII,VIII,IX'],
            'rombel' => ['required', 'string', 'max:2', 'regex:/^[A-Za-z]{1,2}$/'],
            'capacity' => 'sometimes|nullable|integer|min:1|max:100',
            'status' => 'sometimes|nullable|in:active,inactive',
            'homeroom_teacher_id' => 'nullable',
            'assistant_teacher_id' => 'nullable',
        ]);

        if (array_key_exists('homeroom_teacher_id', $validated)) {
            $validated['homeroom_teacher_id'] = $this->resolveTeacherUserId($validated['homeroom_teacher_id']);
            if ($validated['homeroom_teacher_id'] !== null) {
                $user = User::find($validated['homeroom_teacher_id']);
                if ($user && ! $user->hasRole('wali_kelas')) {
                    $user->assignRole('wali_kelas');
                }
            }
        }

        if (array_key_exists('assistant_teacher_id', $validated)) {
            $validated['assistant_teacher_id'] = $this->resolveTeacherUserId($validated['assistant_teacher_id']);
        }

        // Nama kelas = Tingkatan + Rombel, mis. "VII A" atau "VIII B".
        $rombel = strtoupper(trim($validated['rombel']));
        $name = $validated['grade_level'].' '.$rombel;

        // Cegah duplikat nama kelas pada tahun ajaran yang sama (kecuali dirinya sendiri)
        $duplicate = SchoolClass::where('academic_year_id', $academicYearId)
            ->where('name', $name)
            ->where('id', '!=', $class->id)
            ->exists();
        if ($duplicate) {
            return back()->withInput()->withErrors([
                'rombel' => "Kelas {$name} sudah ada pada tahun ajaran ini. Gunakan rombel lain.",
            ]);
        }

        $validated['name'] = $name;
        unset($validated['rombel']);

        $class->update($validated);

        $name = $validated['name'] ?? $class->name;

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas '.$name.' berhasil diupdate.');
    }

    /** Pindahkan massal (naik kelas) sekumpulan siswa ke satu kelas target — via enrollments. */
    public function promote(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'class_id' => 'required|exists:classes,id',
        ]);

        $target = SchoolClass::findOrFail($validated['class_id']);

        // Kelas arsip/nonaktif tidak boleh dipakai sebagai target pindah.
        if ($target->status !== 'active') {
            return redirect()
                ->route('classes.index')
                ->with('error', 'Kelas '.$target->name.' nonaktif (arsip tahun ajaran '.($target->academicYear?->name ?? '-').') — tidak bisa dipakai sebagai tujuan pindah.');
        }

        $targetYearId = $target->academic_year_id;
        $count = 0;

        foreach (Student::whereIn('id', $validated['student_ids'])->get() as $student) {
            // Update kelas aktif di enrollment tahun ajaran target
            $enroll = $student->enrollments()
                ->where('academic_year_id', $targetYearId)
                ->first();
            if ($enroll) {
                $enroll->update(['class_id' => $target->id]);
            } else {
                // Belum terdaftar di tahun ajaran target → buat enrollment baru
                $student->enrollments()->create([
                    'class_id' => $target->id,
                    'academic_year_id' => $targetYearId,
                    'status' => 'active',
                    'enrolled_at' => now(),
                ]);
            }
            $count++;
        }

        return redirect()
            ->route('classes.index')
            ->with('success', $count.' siswa berhasil dipindahkan ke kelas '.$target->name.'.');
    }

    /** Menghapus kelas */
    public function destroy(SchoolClass $class)
    {
        $this->authorizeManagement();

        // cek relasi siswa sebelum hapus (kelas)
        if ($class->students()->count() > 0) {
            return redirect()
                ->route('classes.index')
                ->with('error', 'Kelas '.$class->name.' tidak bisa dihapus karena masih ada siswa.');
        }

        $class->delete();

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas '.$class->name.' berhasil dihapus.');
    }

    /**
     * Ambil daftar guru yang tersedia untuk menjadi wali kelas.
     * Mengambil dari master Data Guru (Teacher), akun user dengan role guru/wali_kelas,
     * serta guru yang saat ini sudah ditugaskan di kelas.
     */
    private function getAvailableHomeroomTeachers($currentClasses = null): \Illuminate\Support\Collection
    {
        $teachers = collect();
        $seenUserIds = [];

        // 1. Data dari menu Data Guru (Teacher)
        $dataGuru = Teacher::with('user')->active()->orderBy('full_name')->get();
        foreach ($dataGuru as $tg) {
            $userId = $tg->user_id;
            if ($userId) {
                $seenUserIds[$userId] = true;
            }
            $teachers->push((object) [
                'id' => $userId ?: ('teacher_'.$tg->id),
                'user_id' => $userId,
                'name' => $tg->full_name,
                'nip' => $tg->nip,
            ]);
        }

        // 2. Akun pengguna dengan role guru atau wali_kelas yang belum tertaut ke Data Guru
        $users = User::with('teacher')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['guru', 'wali_kelas']))
            ->orderBy('name')
            ->get();

        foreach ($users as $u) {
            if (! isset($seenUserIds[$u->id])) {
                $seenUserIds[$u->id] = true;
                $teachers->push((object) [
                    'id' => $u->id,
                    'user_id' => $u->id,
                    'name' => $u->teacher?->full_name ?? $u->name,
                    'nip' => $u->teacher?->nip ?? $u->nip,
                ]);
            }
        }

        // 3. Pastikan wali kelas yang sudah terpasang di kelas saat ini tidak hilang
        if ($currentClasses) {
            foreach ($currentClasses as $c) {
                if ($c->homeroomTeacher && ! isset($seenUserIds[$c->homeroom_teacher_id])) {
                    $seenUserIds[$c->homeroom_teacher_id] = true;
                    $teachers->push((object) [
                        'id' => $c->homeroom_teacher_id,
                        'user_id' => $c->homeroom_teacher_id,
                        'name' => $c->homeroomTeacher->teacher?->full_name ?? $c->homeroomTeacher->name,
                        'nip' => $c->homeroomTeacher->teacher?->nip ?? $c->homeroomTeacher->nip,
                    ]);
                }
            }
        }

        return $teachers->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * Konversi input form wali kelas (user_id, teacher_id, atau teacher_X) menjadi User ID yang valid.
     */
    private function resolveTeacherUserId(mixed $input): ?int
    {
        if (blank($input)) {
            return null;
        }

        if (is_string($input) && str_starts_with($input, 'teacher_')) {
            $teacherId = (int) substr($input, 8);
            $teacher = Teacher::find($teacherId);

            return $teacher?->ensureUserAccount()->id;
        }

        // Jika input merupakan ID user yang valid
        if (User::where('id', $input)->exists()) {
            return (int) $input;
        }

        // Jika input merupakan ID dari tabel teachers
        if ($teacher = Teacher::find($input)) {
            return $teacher->ensureUserAccount()->id;
        }

        return null;
    }
}
