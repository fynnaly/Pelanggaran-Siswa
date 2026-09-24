<?php

namespace App\Http\Controllers;

use App\Models\DisciplineCase;
use App\Models\Student;
use App\Models\ViolationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DisciplineCaseController extends Controller
{
    /** Menampilkan daftar semua pelanggaran berdasarkan status */
    public function index()
    {
        $status = request('status');
        $search = request('search');

        $query = DisciplineCase::with(['student.user', 'violationCategory'])
            ->latest('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                  ->orWhereHas('student', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"));
            });
        }

        $cases = $query->paginate(20)->withQueryString();

        return view('discipline-cases.index', compact('cases', 'status'));
    }

    /** Menampilkan form pelaporan pelanggaran baru */
    public function create()
    {
        $categories = ViolationCategory::where('status', 'active')->get();
        $reporters = User::orderBy('name')->get();

        // Ambil data siswa terpilih jika ada old() (redirect setelah validasi gagal)
        $selectedStudent = null;
        if (old('student_id')) {
            $record = Student::with('schoolClass')
                ->where('status', 'active')
                ->find(old('student_id'));
            if ($record) {
                $selectedStudent = [
                    'id' => $record->id,
                    'full_name' => $record->full_name,
                    'nis' => $record->nis,
                    'kelas' => $record->schoolClass?->name ?? '-',
                ];
            }
        }

        return view('discipline-cases.create', compact('categories', 'reporters', 'selectedStudent'));
    }

    /** Menyimpan pelaporan pelanggaran baru */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'violation_category_id' => 'required|exists:violation_categories,id',
            'reporter_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $case = DisciplineCase::create([
            'case_number' => 'KAS-' . date('Y') . '-' . str_pad((DisciplineCase::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT),
            'student_id' => $validated['student_id'],
            'violation_category_id' => $validated['violation_category_id'],
            'report_by' => $validated['reporter_id'],
            'description' => $validated['notes'] ?? '',
            'status' => 'found',
        ]);

        return redirect()
            ->route('kasus-pelanggaran.index')
            ->with('success', 'Laporan pelanggaran ' . $case->case_number . ' berhasil dicatat (status: found).');
    }

    /** Menampilkan detail pelanggaran tertentu */
    public function show(DisciplineCase $disciplineCase)
    {
        return view('discipline-cases.show', compact('disciplineCase'));
    }

    /** Validasi pelanggaran: found -> validated + potong poin */
    public function validate(Request $request, DisciplineCase $disciplineCase)
    {
        $this->authorize('validate', $disciplineCase);

        $validated = $request->validate([
            'validation_passed' => 'required|boolean',
        ]);

        $violation = $disciplineCase->violationCategory;
        $points = $violation->points;

        if ($validated['validation_passed']) {
            DB::transaction(function () use ($disciplineCase, $violation, $points) {
                $academicYearId = $disciplineCase->student?->schoolClass?->academic_year_id
                    ?? \App\Models\AcademicYear::where('is_active', true)->first()?->id;

                $lastBalance = $disciplineCase->student->pointLedgers()
                    ->lockForUpdate()
                    ->latest('id')
                    ->value('balance_after') ?? 2000;
                $balanceAfter = $lastBalance - $points;

                $disciplineCase->status = 'validated';
                $disciplineCase->validated_by = auth()->id();
                $disciplineCase->validated_at = now();
                $disciplineCase->save();

                $disciplineCase->student->pointLedgers()->create([
                    'student_id' => $disciplineCase->student_id,
                    'academic_year_id' => $academicYearId,
                    'direction' => 'debit',
                    'amount' => $points,
                    'balance_after' => $balanceAfter,
                    'transaction_type' => 'VIOLATION',
                    'source_type' => DisciplineCase::class,
                    'source_id' => $disciplineCase->id,
                    'reason' => 'Pelanggaran: ' . $violation->code,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);
            });

            return redirect()
                ->route('kasus-pelanggaran.index')
                ->with('success', 'Pelanggaran ' . $disciplineCase->case_number . ' diverifikasi, poin ' . $points . ' berhasil dikurang.');
        }

        // Bukti tidak cukup -> dibuang, poin tetap
        $disciplineCase->status = 'dismissed';
        $disciplineCase->save();

        return redirect()
            ->route('kasus-pelanggaran.index')
            ->with('warning', 'Pelanggaran ' . $disciplineCase->case_number . ' dibuang, poin tidak dikurang.');
    }

    /** Buang kasus: found -> dismissed (bukti tidak cukup) */
    public function dismiss(DisciplineCase $disciplineCase)
    {
        $this->authorize('dismiss', $disciplineCase);

        if ($disciplineCase->status !== 'found') {
            return redirect()
                ->route('kasus-pelanggaran.show', $disciplineCase)
                ->with('error', 'Hanya kasus dengan status diproses yang bisa dibuang.');
        }

        $disciplineCase->status = 'dismissed';
        $disciplineCase->save();

        return redirect()
            ->route('kasus-pelanggaran.index')
            ->with('warning', 'Pelanggaran ' . $disciplineCase->case_number . ' dibuang, poin tidak dikurang.');
    }

    /** Selesaikan kasus: validated -> done */
    public function done(DisciplineCase $disciplineCase)
    {
        $this->authorize('done', $disciplineCase);

        if ($disciplineCase->status === 'validated') {
            $disciplineCase->status = 'done';
            $disciplineCase->save();

            return redirect()
                ->route('kasus-pelanggaran.index')
                ->with('success', 'Kasus ' . $disciplineCase->case_number . ' selesai (done).');
        }

        return redirect()
            ->route('kasus-pelanggaran.index')
            ->with('error', 'Kasus hanya bisa diedit dari status validated.');
    }
}
