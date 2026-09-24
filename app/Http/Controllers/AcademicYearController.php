<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Services\ClassPromotionService;
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
        // Validasi
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:academic_years,name',
            'start_date' => 'required|date',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle Checkbox
        $validated['is_active'] = $request->has('is_active');

        // Logika Tahun Ajaran Aktif
        if ($validated['is_active']) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        // Simpan ke Database
        $newRecord = AcademicYear::create($validated);

        // Jika langsung diaktifkan, jalankan promosi
        if ($validated['is_active']) {
            $result = app(ClassPromotionService::class)->promote($newRecord);

            return redirect()
                ->route('tahun-ajaran.index')
                ->with('success', "Tahun ajaran {$newRecord->name} diaktifkan. {$result['classes_renamed']} kelas dipromosikan, {$result['students_graduated']} siswa lulus.");
        }

        // Jika BERHASIL, redirect ke index
        return redirect()
            ->route('tahun-ajaran.index')
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
        // Validasi
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:academic_years,name,' . $academicYear->id,
            'start_date' => 'required|date',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle Checkbox
        $validated['is_active'] = $request->has('is_active');

        // Cek apakah status aktif berubah
        $wasInactive = !$academicYear->is_active;
        $willBeActive = $validated['is_active'];

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

        // Jika baru diaktifkan, jalankan promosi
        if ($wasInactive && $willBeActive) {
            $result = app(ClassPromotionService::class)->promote($academicYear);

            return redirect()
                ->route('tahun-ajaran.index')
                ->with('success', "Tahun ajaran {$academicYear->name} diaktifkan. {$result['classes_renamed']} kelas dipromosikan, {$result['students_graduated']} siswa lulus.");
        }

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('success', 'Tahun ajaran berhasil diupdate.');
    }

    /** Remove the specified resource from storage. */
    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();

        return redirect()
            ->route('tahun-ajaran.index')
            ->with('success', 'Tahun ajaran berhasil dihapus.');
    }
}
