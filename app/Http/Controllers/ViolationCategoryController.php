<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ViolationCategory;

class ViolationCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ViolationCategory::latest('code')
            ->paginate(20);

            // Mengambalikan atau mendorong ke halaman
            return view('violation-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // mengarah ke halaman, folder violation-create dan file create.blade.php
        return view('violation-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:violation_categories,code'],
            'name' => ['required', 'string', 'max:150'],
            'severity' => ['required', 'in:ringan,sedang,berat'],
            'points' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,active'],
        ]);

        ViolationCategory::create($validated);

        // mengarahkan ke halaman ke -
        return redirect()
        ->route('violation-categories.index')
        ->with('success', 'Kategori pelanggaran ' . $validated['code'] . ' berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ViolationCategory $violationCategory)
    {
        return view('violation-categories.edit', compact('violationCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Violationcategory $violationCategory)
    {
        $validated = $request->validate([
            'code' => ['sometimes','required', 'string', 'max:20', 'unique:violation_categories,code,' . $violationCategory->id],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'severity' => ['sometimes', 'required', 'in:ringan,sedang,berat'],
            'points' => ['sometimes', 'required', 'integer', 'min:1'],
            'status' => ['sometimes', 'required', 'in:draft,active'],
        ]);

        $violationCategory->update($validated);

        return redirect()
            ->route('violation-categories.index')
            ->with('success','Berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ViolationCategory $violationCategory)
    {

        $violationCategory->delete();

        return redirect()
        ->route('violation-categories.index')
        ->with('success', 'Berhasil dihapus');

    }
}
