<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicYear;
use DB;

class AcademicYearController extends Controller
{
    /** Display a listing of the resource. */
    public function index()
    {
        $academicYears = AcademicYear::orderBy('name')
            ->paginate(20);

        return view('academic-years.index', compact('academicYears'));
    }

    /** Show the form for creating a new resource. */
    public function create()
    {
        return view('academic-years.create');
    }

    /** Store a newly created resource in storage. */
    public function store(Request $request)
    {

        // Vaidasi
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:academic_years,name',
            'start_date' => 'required|date',
            'is_active' => 'nullable|boolean',
        ]);

        // dd($validated->all);
        // Handle Checkbox
        $validated['is_active'] = $request->has('is_active');

        // Logika Tahun Ajaran Aktif
        if ($validated['is_active']) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        // Simpan ke Database
        $newRecord = AcademicYear::create($validated);

        // Jika BERHASIL, redirect ke index
        return redirect()
            ->route('academic-years.index')
            ->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    /** Show the form for editing the specified resource. */
    public function edit(AcademicYear $academicYear)
    {
        return view('academic-years.edit', compact('academicYear'));
    }

    /** Update the specified resource in storage. */
    public function update(Request $request, AcademicYear $academicYear)
    {

        // Vaidasi
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:academic_years,name,' . $academicYear->id,
            'start_date' => 'required|date',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle Checkbox
        $validated['is_active'] = $request->has('is_active');

        // Gunakan transaksi untuk logika aktif tahun ajaran
        DB::transaction(function () use ($validated, $academicYear) {
            // Jika menyetel sebagai aktif, nonaktifkan tahun ajaran lain
            if ($validated['is_active']) {
                AcademicYear::where('is_active', true)
                    ->where('id', '!=', $academicYear->id)
                    ->update(['is_active' => false]);
            }

            // Update data tahun ajaran
            $academicYear->update($validated);
        });

        return redirect()
            ->route('academic-years.index')
            ->with('success', 'Tahun ajaran berhasil diupdate.');
    }

    /** Remove the specified resource from storage. */
    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();

        return redirect()
            ->route('academic-years.index')
            ->with('success', 'Tahun ajaran berhasil dihapus.');
    }
}