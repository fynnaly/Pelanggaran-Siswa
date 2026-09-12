<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    /** Menampilkan daftar semua kelas */
    public function index()
    {
        $classes = SchoolClass::with(['academicYear', 'homeroomTeacher', 'students'])
            ->latest('name')
            ->paginate(20);

        return view('classes.index', compact('classes'));
    }

    /** Menampilkan form pembuatan kelas baru */
    public function create()
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->pluck('name', 'id');
        return view('classes.create', compact('academicYears'));
    }

    /** Menyimpan kelas baru ke database */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => ['required', 'string', 'max:20', 'unique:classes,name,' . $request->academic_year_id . ',academic_year_id'],
            'homeroom_teacher_id' => 'nullable|exists:users,id',
        ]);

        SchoolClass::create($validated);

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas ' . $validated['name'] . ' berhasil ditambahkan.');
    }

    /** Menampilkan form edit kelas */
    public function edit(SchoolClass $class)
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->pluck('name', 'id');
        return view('classes.edit', compact('class', 'academicYears'));
    }

    /** Memperbarui data kelas */
    public function update(Request $request, SchoolClass $class)
    {
        $validated = $request->validate([
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'name' => ['sometimes', 'required', 'string', 'max:20',
                'unique:classes,name,' . $class->id . ',academic_year_id,' .
                $request->input('academic_year_id', $class->academic_year_id) . ',academic_year_id'],
            'homeroom_teacher_id' => 'nullable|exists:users,id',
        ]);

        $class->update($validated);

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas ' . $validated['name'] . ' berhasil diupdate.');
    }

    /** Menghapus kelas */
    public function destroy(SchoolClass $class)
    {
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
