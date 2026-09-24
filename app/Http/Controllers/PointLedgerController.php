<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PointLedger;
use App\Models\Student;

class PointLedgerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->string('search')->trim();
        $direction = $request->string('direction')->toString();
        $type = $request->string('type')->toString();

        $query = PointLedger::with(['student.schoolClass', 'academicYear'])
            ->when($search, fn($q, $s) => $q->whereHas('student', fn($sq) =>
                $sq->where('full_name', 'like', "%{$s}%")
                    ->orWhere('nisn', 'like', "%{$s}%")
            ))
            ->when($direction, fn($q, $d) => $q->where('direction', $d))
            ->when($type, fn($q, $t) => $q->where('transaction_type', $t));

        $ledgers = $query->latest()->paginate(20)->withQueryString();

        return view('point-ledgers.index', compact(
            'ledgers', 'search', 'direction', 'type'
        ));
    }

    /**
     * Display the specified resource.
     */
    public function show(PointLedger $pointLedger)
    {
        $pointLedger->load(['student.schoolClass', 'academicYear', 'creator', 'verifier']);

        $student = $pointLedger->student;

        // Ambil semua transaksi siswa di tahun ajaran yang sama
        $ledger = PointLedger::where('student_id', $student->id)
            ->where('academic_year_id', $pointLedger->academic_year_id)
            ->latest()
            ->get();

        return view('point-ledgers.show', compact('pointLedger', 'student', 'ledger'));
    }
}
