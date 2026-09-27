<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    /** Menampilkan daftar semua kelas */
    public function index()
    {
        $academicYears = AcademicYear::orderBy('start_date')->get();
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $selectedYearId = request('year_id') ?: $activeYearId;

        $classes = SchoolClass::with(['academicYear', 'homeroomTeacher', 'assistantTeacher'])->withCount('students')
            ->where('academic_year_id', $selectedYearId)
            ->latest('name')
            ->paginate(20);
        $teachers = User::orderBy('name')->get(['id', 'name']);

        return view('classes.index', compact('classes', 'academicYears', 'selectedYearId', 'teachers'));
    }

    /** Menampilkan detail satu kelas beserta daftarnya */
    public function show(SchoolClass $class)
    {
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
                if ($minOk && $maxOk) { $zoneCounts[$z['name']]++; break; }
            }
        }

        return view('classes.show', compact('class', 'students', 'zoneCounts'));
    }

    /** Menampilkan form pembuatan kelas baru */
    public function create()
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $users = User::orderBy('name')->get();
        return view('classes.create', compact('academicYears', 'users'));
    }

    /** Menyimpan kelas baru ke database */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => ['required', 'string', 'max:50',
                Rule::unique('classes', 'name')->where(function ($query) use ($request) {
                    return $query->where('academic_year_id', $request->academic_year_id);
                })
            ],
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'assistant_teacher_id' => 'nullable|exists:users,id|different:homeroom_teacher_id',
        ]);

        SchoolClass::create($validated);

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas ' . $validated['name'] . ' berhasil ditambahkan.');
    }

    /** Menampilkan form edit kelas */
    public function edit(SchoolClass $class)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $users = User::orderBy('name')->get();
        return view('classes.edit', compact('class', 'academicYears', 'users'));
    }

    /** Memperbarui data kelas */
    public function update(Request $request, SchoolClass $class)
    {
        // amvil data ID tahun ajaran
        $academicYearId = $request->input('academic_year_id', $class->academic_year_id);

        $validated = $request->validate([
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'name' => ['sometimes', 'required', 'string', 'max:50',
                Rule::unique('classes', 'name')
                ->ignore($class->id)
                ->where('academic_year_id', $academicYearId),
            ],
            'grade_level' => 'sometimes|nullable|in:X,XI,XII',
            'major' => 'sometimes|nullable|string|max:50',
            'capacity' => 'sometimes|nullable|integer|min:1|max:100',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'assistant_teacher_id' => 'nullable|exists:users,id|different:homeroom_teacher_id',
        ]);

        $class->update($validated);

        $name = $validated['name'] ?? $class->name;
        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas ' . $name . ' berhasil diupdate.');
    }

    /** Pindahkan massal (naik kelas) sekumpulan siswa ke satu kelas target — via enrollments. */
    public function promote(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'class_id' => 'required|exists:classes,id',
        ]);

        $target = SchoolClass::findOrFail($validated['class_id']);
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
            ->with('success', $count . ' siswa berhasil dipindahkan ke kelas ' . $target->name . '.');
    }

    /** Menghapus kelas */
    public function destroy(SchoolClass $class)
    {

    // cek relasi siswa sebelum hapus (kelas)
        if ($class->students()->count() > 0) {
            return redirect()
                ->route('classes.index')
                ->with('error', 'Kelas ' . $class->name . ' tidak bisa dihapus karena masih ada siswa.');
        }

        $class->delete();

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas ' . $class->name . ' berhasil dihapus.');
    }
}
