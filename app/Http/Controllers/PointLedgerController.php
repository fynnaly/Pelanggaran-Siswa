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
     * Riwayat poin satu siswa — dipakai tombol "Detail" di Monitor Poin.
     *
     * Monitor Poin menampilkan baris per SISWA, sedangkan show() butuh id
     * ledger tunggal. Method ini menjadi jembatannya: tampilkan seluruh
     * ledger siswa (tahun ajaran terakhir yang punya transaksi) dengan
     * tampilan yang sama persis.
     */
    public function forStudent(Student $student)
    {
        // Ambil tahun ajaran terakhir yang punya transaksi untuk siswa ini,
        // supaya kolom "Saldo" (balance_after) tetap bermakna (per-tahun).
        $yearId = PointLedger::where('student_id', $student->id)
            ->latest('id')
            ->value('academic_year_id');

        $student->load('currentEnrollment.schoolClass');
        $ledger = PointLedger::with(['source'])
            ->where('student_id', $student->id)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->latest()
            ->get();

        // View point-ledgers/show tidak merujuk $pointLedger; cukup $student + $ledger.
        return view('point-ledgers.show', ['pointLedger' => null, 'student' => $student, 'ledger' => $ledger]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PointLedger $pointLedger)
    {
        $pointLedger->load(['student.currentEnrollment.schoolClass', 'academicYear', 'creator', 'verifier']);

        $student = $pointLedger->student;

        // Ambil semua transaksi siswa di tahun ajaran yang sama
        $ledger = PointLedger::with(['source'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $pointLedger->academic_year_id)
            ->latest()
            ->get();

        return view('point-ledgers.show', compact('pointLedger', 'student', 'ledger'));
    }
}
