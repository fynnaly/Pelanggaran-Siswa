<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PointLedger;

class PointLedgerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ledgers = PointLedger::with(['student', 'academicYear'])
            ->latest()->paginate(20);

        return view('point-ledgers.index', compact('ledgers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    // public function create()
    // {
    //     //
    // }

    /**
     * Store a newly created resource in storage.
     */

    // public function store(Request $request)
    // {
    //     //
    // }

    /**
     * Display the specified resource.
     */
    public function show(PointLedger $pointLedger)
    {
        return view('point-ledgers.show', compact('pointLedger'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    // public function edit(string $id)
    // {
    //     //
    // }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     //
    // }
}
