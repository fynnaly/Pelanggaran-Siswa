<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
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
        $allowed = ['id', 'name', 'grade_level', 'academic_year_id', 'status', 'students_count'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'id';

        $query = SchoolClass::with(['academicYear', 'homeroomTeacher', 'assistantTeacher'])->withCount('students')
            ->where('academic_year_id', $selectedYearId);

        // Scope wali kelas: null = role pengawas/penuh, array = wajib filter (bisa kosong = tidak ada kelas binaan)
        $waliScope = $this->waliKelasScope();
        if ($waliScope !== null) {
            $query->whereIn('id', $waliScope);
        }

        $classes = $query->orderBy($sort, $order)
            ->paginate(20)
            ->withQueryString();
        // Hanya role 'guru' yang bisa jadi wali kelas (skill: filter whereHas('roles')).
        $teachers = User::whereHas('roles', fn ($q) => $q->where('name', 'guru'))
            ->orderBy('name')->get(['id', 'name']);

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

        $class->load(['academicYear', 'homeroomTeacher', 'assistantTeacher']);
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

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $users = User::whereHas('roles', fn ($q) => $q->where('name', 'guru'))->orderBy('name')->get();

        $activeYear = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $hasPrerequisites = (bool) $activeYear;
        $prerequisites = [
            [
                'label' => 'Tahun Ajaran',
                'ready' => (bool) $activeYear,
                'url' => route('tahun-ajaran.create'),
                'action' => 'Buat Tahun Ajaran',
            ],
        ];

        return view('classes.create', compact('academicYears', 'users', 'hasPrerequisites', 'prerequisites'));
    }

    /** Menyimpan kelas baru ke database */
    public function store(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_level' => ['required', 'in:VII,VIII,IX'],
            'rombel' => ['required', 'string', 'max:2', 'regex:/^[A-Za-z0-9]{1,2}$/'],
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'assistant_teacher_id' => 'nullable|exists:users,id|different:homeroom_teacher_id',
            'status' => 'nullable|in:active,inactive',
        ]);
        $validated['status'] ??= 'active';

        // Nama kelas = Tingkatan + Rombel, mis. "VII 1" atau "VII A".
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
        $users = User::whereHas('roles', fn ($q) => $q->where('name', 'guru'))->orderBy('name')->get();

        return view('classes.edit', compact('class', 'academicYears', 'users'));
    }

    /** Memperbarui data kelas */
    public function update(Request $request, SchoolClass $class)
    {
        $this->authorizeManagement();

        // amvil data ID tahun ajaran
        $academicYearId = $request->input('academic_year_id', $class->academic_year_id);

        $validated = $request->validate([
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'grade_level' => ['required', 'in:VII,VIII,IX'],
            'rombel' => ['required', 'string', 'max:2', 'regex:/^[A-Za-z0-9]{1,2}$/'],
            'capacity' => 'sometimes|nullable|integer|min:1|max:100',
            'status' => 'sometimes|nullable|in:active,inactive',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'assistant_teacher_id' => 'nullable|exists:users,id|different:homeroom_teacher_id',
        ]);

        // Nama kelas = Tingkatan + Rombel, mis. "VII 1" atau "VII A".
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
}
