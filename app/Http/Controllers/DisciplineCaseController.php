<?php

namespace App\Http\Controllers;

use App\Models\DisciplineCase;
use App\Models\ViolationCategory;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\PointLedger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DisciplineCaseController extends Controller
{
    use AuthorizesRequests;

    /** Menampilkan daftar semua pelanggaran berdasarkan status */
    public function index()
    {
        $status = request('status');
        $search = request('search');
        $sort = request()->string('sort')->toString();
        $order = strtolower(request()->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'created_at', 'status', 'case_number', 'student_id', 'violation_category_id'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'id';
        $academicYearId = AcademicYear::where('is_active', true)->first()?->id;

        $query = DisciplineCase::with(['student.currentEnrollment.schoolClass', 'schoolClass', 'violationCategory', 'reporter'])
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->orderBy($sort, $order);

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

        return view('discipline-cases.index', compact('cases', 'status', 'sort', 'order'));
    }

    /** Menampilkan form pelaporan pelanggaran baru */
    public function create()
    {
        $categories = ViolationCategory::where('status', 'active')->get();
        $classes = SchoolClass::where('academic_year_id', AcademicYear::where('is_active', true)->value('id'))
            ->orderBy('name')->get();
        $reporters = User::whereNot('id', auth()->id())->orderBy('name')->get();

        $selectedStudent = null;
        if (old('student_id')) {
            $record = Student::with('currentEnrollment.schoolClass')
                ->where('status', 'active')
                ->find(old('student_id'));
            if ($record) {
                $selectedStudent = [
                    'id' => $record->id,
                    'full_name' => $record->full_name,
                    'nis' => $record->nis,
                    'kelas' => $record->currentEnrollment?->schoolClass?->name ?? '-',
                ];
            }
        }

        return view('discipline-cases.create', compact('categories', 'classes', 'reporters', 'selectedStudent'));
    }

    /** Menyimpan pelaporan pelanggaran baru */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'violation_category_id' => 'required|exists:violation_categories,id',
            'incident_date' => 'nullable|date',
            'incident_time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:100',
            'class_id' => 'nullable|exists:classes,id',
            // Form kirim "notes"; API lama kirim "description". Terima keduanya.
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'reporter_id' => 'nullable|exists:users,id',
        ]);

        $description = $validated['description'] ?? $validated['notes'] ?? null;

        if (! $description) {
            return back()
                ->withInput()
                ->withErrors(['notes' => 'Keterangan / kronologi wajib diisi.']);
        }

        // Snapshot konteks saat kejadian: ambil dari enrollment siswa pada tahun kejadian.
        $incidentYear = AcademicYear::where('is_active', true)->first();
        $student = Student::with(['enrollments.schoolClass'])->findOrFail($validated['student_id']);
        $enrollment = $student->enrollments
            ->firstWhere('academic_year_id', $incidentYear?->id)
            ?? $student->enrollments->firstWhere('status', 'active')
            ?? $student->enrollments->sortByDesc('id')->first();

        // Kelas wajib untuk kasus resmi. Tanpa enrollment, izinkan kelas dipilih manual.
        $classId = $validated['class_id'] ?? $enrollment?->class_id;
        if (! $classId) {
            return back()
                ->withInput()
                ->with('error', 'Siswa ini belum terdaftar di kelas pada tahun ajaran berjalan. '
                    . 'Pilih kelas manual atau daftarkan siswa ke kelas terlebih dahulu.');
        }

        $academicYearId = $enrollment?->academic_year_id ?? $incidentYear?->id;

        $cat = ViolationCategory::find($validated['violation_category_id']);
        $case = DisciplineCase::create([
            'case_number' => DisciplineCase::generateCaseNumber(),
            'student_id' => $validated['student_id'],
            'enrollment_id' => $enrollment?->id,
            'class_id' => $classId,
            'violation_category_id' => $validated['violation_category_id'],
            'academic_year_id' => $academicYearId,
            'reporter_id' => $validated['reporter_id'] ?? auth()->id(),
            'location' => $validated['location'] ?? null,
            'incident_date' => $validated['incident_date'] ?? now()->toDateString(),
            'incident_time' => $validated['incident_time'] ?? null,
            'description' => $description,
            'status' => 'found',
            'is_recurring' => $cat->is_recurring ?? false,
        ]);

        return redirect()
            ->route('kasus-pelanggaran.index')
            ->with('success', 'Laporan pelanggaran ' . $case->case_number . ' berhasil dicatat (status: found).');
    }

    /** Menampilkan detail pelanggaran tertentu */
    public function show(DisciplineCase $disciplineCase)
    {
        $disciplineCase->load([
            'student.currentEnrollment.schoolClass', 'enrollment.schoolClass', 'schoolClass', 'violationCategory', 'reporter', 'academicYear',
            'assignedOfficer', 'validator', 'closer',
        ]);
        return view('discipline-cases.show', compact('disciplineCase'));
    }

    /**
     * Update status kasus (found → clarifying → classifying → handling → monitoring → closed).
     * Transisi ke 'handling' membuat debit poin ke ledger.
     */
    public function updateStatus(Request $request, DisciplineCase $disciplineCase)
    {
        $this->authorize('update', $disciplineCase);

        $validated = $request->validate([
            'status' => 'required|in:clarifying,classifying,handling,monitoring,closed',
            'notes' => 'nullable|string',
        ]);

        $newStatus = $validated['status'];

        // Validasi: pindah ke handling → buat debit ledger dalam transaksi atomik
        if ($newStatus === 'handling') {
            DB::transaction(function () use ($disciplineCase, $validated) {
                $disciplineCase->update([
                    'status' => 'handling',
                    'validator_id' => auth()->id(),
                    'validated_at' => now(),
                    'validation_notes' => $validated['notes'] ?? null,
                ]);

                $academicYearId = $disciplineCase->academic_year_id;
                $lastBalance = PointLedger::getLastBalance($disciplineCase->student_id, $academicYearId);
                $points = $disciplineCase->getEffectivePoints();
                $newBalance = $lastBalance - $points;

                PointLedger::create([
                    'student_id' => $disciplineCase->student_id,
                    'academic_year_id' => $academicYearId,
                    'direction' => PointLedger::DIR_DEBIT,
                    'amount' => $points,
                    'balance_after' => $newBalance,
                    'transaction_type' => PointLedger::TYPE_VIOLATION,
                    'source_type' => DisciplineCase::class,
                    'source_id' => $disciplineCase->id,
                    'reason' => 'Pelanggaran: ' . $disciplineCase->violationCategory->code,
                    'created_by' => auth()->id(),
                ]);
            });

            return redirect()
                ->route('kasus-pelanggaran.show', $disciplineCase)
                ->with('success', 'Kasus divalidasi, poin ' . $disciplineCase->getEffectivePoints() . ' berhasil dikurang.');
        }

        // Transisi lainnya
        $updates = ['status' => $newStatus];

        // Catatan per tahap
        $notesField = match ($newStatus) {
            'clarifying' => 'clarification_notes',
            'classifying' => 'classification_notes',
            'monitoring' => 'handling_notes',
            'closed' => 'monitoring_notes',
            default => null,
        };
        if ($notesField && ! empty($validated['notes'])) {
            $updates[$notesField] = $validated['notes'];
        }

        if ($newStatus === 'closed') {
            $updates['closed_at'] = now();
            $updates['closed_by'] = auth()->id();
        }

        $disciplineCase->update($updates);

        return redirect()
            ->route('kasus-pelanggaran.show', $disciplineCase)
            ->with('success', 'Kasus diperbarui ke status: ' . $newStatus);
    }

    /** Buang kasus: status → dismissed */
    public function dismiss(DisciplineCase $disciplineCase)
    {
        $this->authorize('dismiss', $disciplineCase);

        $disciplineCase->update([
            'status' => 'dismissed',
            'validator_id' => auth()->id(),
            'validated_at' => now(),
            'validation_notes' => request('validation_notes'),
        ]);

        return redirect()
            ->route('kasus-pelanggaran.index')
            ->with('warning', 'Pelanggaran ' . $disciplineCase->case_number . ' ditolak.');
    }
}
