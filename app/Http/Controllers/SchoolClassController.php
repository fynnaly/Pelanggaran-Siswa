<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $users = User::orderBy('name')->get();
        return view('classes.create', compact('academicYears', 'users'));
    }

    /** Menyimpan kelas baru ke database */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => ['required', 'string', 'max:20',

            /**
             * ! SQLSTATE[42703]: Undefined column: 7 ERROR: column "6" does not exist LINE :
             * * Fix, menggunakan Rule::unique qihuy
            */
            Rule::unique('classes', 'name')->where(function ($query) use ($request) {
                return $query->where('academic_year_id', $request->academic_year_id);
            })
            ],
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
        // amvil data ID tahun ajaran
        $academicYearId = $request->input('academic_year_id', $class->academic_year_id);

        $validated = $request->validate([
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'name' => ['sometimes', 'required', 'string', 'max:20',
            // cari naam yg sama, but ga anggap Id kelas yang dipilih dan juga cek di taun ajaran yang sama juga.
            Rule::unique('classes', 'name')
            ->ignore($class->id)
            ->where('academic_year_id', $academicYearId),
            ],
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
