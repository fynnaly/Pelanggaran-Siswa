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
        $sort = $request->string('sort')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'created_at', 'amount', 'balance_after', 'direction', 'transaction_type'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'created_at';

        $query = PointLedger::with(['student.currentEnrollment.schoolClass', 'academicYear'])
            ->when($search, fn($q, $s) => $q->whereHas('student', fn($sq) =>
                $sq->where('full_name', 'like', "%{$s}%")
                    ->orWhere('nisn', 'like', "%{$s}%")
            ))
            ->when($direction, fn($q, $d) => $q->where('direction', $d))
            ->when($type, fn($q, $t) => $q->where('transaction_type', $t));

        $ledgers = $sort === 'created_at'
            ? $query->latest()->paginate(20)->withQueryString()
            : $query->orderBy($sort, $order)->paginate(20)->withQueryString();

        return view('point-ledgers.index', compact(
            'ledgers', 'search', 'direction', 'type', 'sort', 'order'
        ));
    }

    /**
     * Display the specified resource.
     */
    public function show(PointLedger $pointLedger)
    {
        $pointLedger->load(['student.currentEnrollment.schoolClass', 'academicYear', 'creator', 'verifier']);

        $student = $pointLedger->student;

        // Ambil semua transaksi siswa di tahun ajaran yang sama
        $ledger = PointLedger::where('student_id', $student->id)
            ->where('academic_year_id', $pointLedger->academic_year_id)
            ->latest()
            ->get();

        return view('point-ledgers.show', compact('pointLedger', 'student', 'ledger'));
    }
}
