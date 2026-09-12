<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /** Menampilkan daftar semua siswa */
    public function index()
    {
        $students = Student::with(['user', 'schoolClass', 'pointLedgers'])
            ->latest('nisn')
            ->paginate(20);

        return view('students.index', compact('students'));
    }

    /** Menampilkan form pembuatan siswa baru */
    public function create()
    {
        return view('students.create');
    }

    /** Menyimpan siswa baru ke database */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nisn' => 'required|string|max:20|unique:students',
            'nis' => 'required|string|max:20|unique:students',
            'full_name' => 'required|string|max:100',
            'class_id' => 'required|exists:classes,id',
            'status' => 'required|in:active,inactive,graduated,transferred',
        ]);

        $user = null;
        if ($request->filled('email')) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => $request->email],
                ['name' => $validated['full_name'], 'password' => bcrypt('temp123')]
            );
        }

        Student::create([
            'user_id' => $user?->id,
            'class_id' => $validated['class_id'],
            'nisn' => $validated['nisn'],
            'nis' => $validated['nis'],
            'full_name' => $validated['full_name'],
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('students.index')
            ->with('success', 'Siswa ' . $validated['full_name'] . ' berhasil ditambahkan.');
    }

    /** Menampilkan form edit siswa */
    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    /** Memperbarui data siswa */
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'nisn' => 'sometimes|required|string|max:20|unique:students,nisn,' . $student->id,
            'nis' => 'sometimes|required|string|max:20|unique:students,nis,' . $student->id,
            'full_name' => 'sometimes|required|string|max:100',
            'class_id' => 'sometimes|exists:classes,id',
            'status' => 'sometimes|in:active,inactive,graduated,transferred',
        ]);

        $student->update($validated);

        return redirect()
            ->route('students.index')
            ->with('success', 'Data siswa ' . $student->full_name . ' berhasil diupdate.');
    }

    /** Menghapus siswa */
    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('success', 'Siswa ' . $student->full_name . ' berhasil dihapus.');
    }
}
