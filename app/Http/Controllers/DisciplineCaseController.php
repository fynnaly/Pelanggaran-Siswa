<?php

namespace App\Http\Controllers;

use App\Models\DisciplineCase;
use App\Models\Student;
use App\Models\ViolationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DisciplineCaseController extends Controller
{
    /** Menampilkan daftar semua pelanggaran berdasarkan status */
    public function index()
    {
        $status = request('status'); // found, validated, dismissed, done

        $query = DisciplineCase::with(['student.user', 'violationCategory'])
            ->latest('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $cases = $query->paginate(20);

        return view('discipline-cases.index', compact('cases', 'status'));
    }

    /** Menampilkan form pelaporan pelanggaran baru */
    public function create()
    {
        $students = Student::where('status', 'active')
            ->pluck('full_name', 'id');

        $categories = ViolationCategory::where('status', 'active')
            ->pluck('name', 'id');

        return view('discipline-cases.create', compact('students', 'categories'));
    }

    /** Menyimpan pelaporan pelanggaran baru */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'violation_category_id' => 'required|exists:violation_categories,id',
            'report_by' => 'required|exists:users,id',
            'location' => 'nullable|string|max:100',
            'description' => 'required|string',
            'status' => 'required|in:found',
        ]);

        $case = DisciplineCase::create([
            'case_number' => 'KAS-' . date('Y') . '-' . str_pad(DisciplineCase::max('id') ?? 0 + 1, 4, '0', STR_PAD_LEFT),
            'student_id' => $validated['student_id'],
            'violation_category_id' => $validated['violation_category_id'],
            'report_by' => $validated['report_by'],
            'location' => $validated['location'],
            'description' => $validated['description'],
            'status' => 'found',
        ]);

        return redirect()
            ->route('discipline-cases.index')
            ->with('success', 'Laporan pelanggaran ' . $case->case_number . ' berhasil dicatat (status: found).');
    }

    /** Menampilkan detail pelanggaran tertentu */
    public function show(DisciplineCase $case)
    {
        return view('discipline-cases.show', compact('case'));
    }

    /** Validasi pelanggaran: found -> validated + potong poin */
    public function validate(Request $request, DisciplineCase $case)
    {
        $this->authorize('validate', $case);

        $validated = $request->validate([
            'validation_passed' => 'required|boolean',
        ]);

        if ($validated['validation_passed']) {
            // Ubah status jadi validated
            $case->status = 'validated';
            $case->validated_by = auth()->id();
            $case->validated_at = now();
            $case->save();

            // Buat ledger debit (kurangi poin)
            $violation = $case->violationCategory;
            $points = $violation->points;

            $case->student->pointLedgers()->create([
                'student_id' => $case->student_id,
                'academic_year_id' => $case->student->academicYear->id,
                'direction' => 'debit',
                'amount' => $points,
                'balance_after' => $case->student->pointLedgers()->latest('id')->value('balance_after') ?? 2000 - $points,
                'transaction_type' => 'VIOLATION',
                'source_type' => DisciplineCase::class,
                'source_id' => $case->id,
                'reason' => 'Pelanggaran: ' . $violation->code,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            return redirect()
                ->route('discipline-cases.index')
                ->with('success', 'Pelanggaran ' . $case->case_number . ' diverifikasi, poin ' . $points . ' berhasil dikurang.');
        } else {
            // Status dibubut tapi bukti tidak cukup -> dismissed (poin tetap, kasus dibuang)
            $case->status = 'dismissed';
            $case->save();

            return redirect()
                ->route('discipline-cases.index')
                ->with('warning', 'Pelanggaran ' . $case->case_number . ' dibuang, poin tidak dikurang.');
        }
    }

    /** Selesaikan kasus: validated -> done */
    public function done(DisciplineCase $case)
    {
        $this->authorize('done', $case);

        if ($case->status === 'validated') {
            $case->status = 'done';
            $case->save();

            return redirect()
                ->route('discipline-cases.index')
                ->with('success', 'Kasus ' . $case->case_number . ' selesai (done).');
        }

        return redirect()
            ->route('discipline-cases.index')
            ->with('error', 'Kasus hanya bisa diedit dari status validated.');
    }
}
