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
        $allowed = ['id', 'created_at', 'incident_date', 'status', 'case_number', 'student_id', 'violation_category_id'];
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
        $this->authorize('create', DisciplineCase::class);

        // Dropdown 1 = kategori induk, Dropdown 2 = sub-kategori (isi kategori).
        $categoryGroups = ViolationCategory::where('status', 'active')
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('status', 'active')->orderBy('name')])
            ->orderBy('name')
            ->get();
        // Data lama flat (induk tanpa anak) tetap selectable agar kasus lama bisa dibuat.
        $categories = ViolationCategory::where('status', 'active')->with('parent')->orderBy('name')->get();
        $classes = SchoolClass::where('academic_year_id', AcademicYear::where('is_active', true)->value('id'))
            ->orderBy('name')->get();

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

        // Pertahankan pilihan kategori saat validasi 422 melempar kembali.
        $selectedGroupId = null;
        if (old('violation_category_id')) {
            $oldSub = ViolationCategory::find(old('violation_category_id'));
            $selectedGroupId = $oldSub?->parent_id ?? $oldSub?->id;
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        $hasActiveYear = (bool) $activeYear;
        $hasClasses = $classes->isNotEmpty();
        $hasStudents = Student::where('status', 'active')->exists();
        $hasCategories = ViolationCategory::where('status', 'active')->whereNotNull('parent_id')->exists();
        $hasPrerequisites = $hasActiveYear && $hasClasses && $hasStudents && $hasCategories;

        $prerequisites = [
            [
                'label' => 'Tahun Ajaran',
                'ready' => $hasActiveYear,
                'url' => route('tahun-ajaran.create'),
                'action' => 'Buat Tahun Ajaran',
            ],
            [
                'label' => 'Kelas',
                'ready' => $hasClasses,
                'url' => route('classes.create'),
                'action' => 'Buat Kelas',
            ],
            [
                'label' => 'Siswa',
                'ready' => $hasStudents,
                'url' => route('students.create'),
                'action' => 'Tambah Siswa',
            ],
            [
                'label' => 'Kategori Pelanggaran',
                'ready' => $hasCategories,
                'url' => route('violation-categories.create'),
                'action' => 'Tambah Kategori',
            ],
        ];

        return view('discipline-cases.create', compact('categories', 'categoryGroups', 'selectedGroupId', 'classes', 'selectedStudent', 'hasPrerequisites', 'prerequisites'));
    }

    /** Menyimpan pelaporan pelanggaran baru */
    public function store(Request $request)
    {
        $this->authorize('create', DisciplineCase::class);

        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'violation_category_id' => ['required', 'exists:violation_categories,id', function ($attr, $value, $fail) {
                // Kasus wajib menunjuk sub-kategori; kategori induk yang masih punya anak tidak boleh dipilih.
                if (\App\Models\ViolationCategory::whereNull('parent_id')->whereKey($value)->whereHas('children')->exists()) {
                    $fail('Pilih sub-kategori, bukan kategori induk.');
                }
            }],
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

        $studentIds = collect($validated['student_ids'] ?? [])
            ->push($validated['student_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return back()->withInput()->with('error', 'Pilih minimal satu siswa pelanggar.');
        }

        // Snapshot konteks saat kejadian: ambil dari enrollment siswa pada tahun kejadian.
        $incidentYear = AcademicYear::where('is_active', true)->first();
        $cat = ViolationCategory::find($validated['violation_category_id']);
        $createdCases = [];

        DB::transaction(function () use ($studentIds, $validated, $description, $incidentYear, $cat, &$createdCases) {
            foreach ($studentIds as $sid) {
                $student = Student::with(['enrollments.schoolClass'])->findOrFail($sid);
                $enrollment = $student->enrollments
                    ->firstWhere('academic_year_id', $incidentYear?->id)
                    ?? $student->enrollments->firstWhere('status', 'active')
                    ?? $student->enrollments->sortByDesc('id')->first();

                // Kelas wajib untuk kasus resmi. Ambil dari enrollment atau manual.
                $classId = $validated['class_id'] ?? $enrollment?->class_id;
                $academicYearId = $enrollment?->academic_year_id ?? $incidentYear?->id;

                $createdCases[] = DisciplineCase::create([
                    'case_number' => DisciplineCase::generateCaseNumber(),
                    'student_id' => $sid,
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
            }
        });

        $count = count($createdCases);
        $msg = $count > 1
            ? "{$count} laporan pelanggaran berhasil dicatat secara kolektif (status: Temuan)."
            : 'Laporan pelanggaran ' . $createdCases[0]->case_number . ' berhasil dicatat (status: Temuan).';

        return redirect()
            ->route('kasus-pelanggaran.index')
            ->with('success', $msg);
    }

    /** Menampilkan detail pelanggaran tertentu */
    public function show(DisciplineCase $disciplineCase)
    {
        $disciplineCase->load([
            'student.currentEnrollment.schoolClass', 'enrollment.schoolClass', 'schoolClass',
            'violationCategory.parent', 'violationCategory.redemptionRules.achievementCategory',
            'reporter', 'academicYear', 'assignedOfficer', 'validator', 'closer',
        ]);
        $recovery = $disciplineCase->violationCategory
            ? app(\App\Http\Controllers\ViolationCategoryController::class)->recoveryFor($disciplineCase->violationCategory)
            : [];
        return view('discipline-cases.show', compact('disciplineCase', 'recovery'));
    }

    /**
     * Terapkan transisi status pada satu kasus — DIPAKAI BERSAMA oleh
     * updateStatus (satu kasus) dan bulkStatus (massal) supaya aturan
     * kolom/notes/debit tidak terpecah dua.
     *
     * Return true bila transisi ini membuat debit poin baru ke ledger.
     *
     * Catatan: debit hanya dibuat SEKALI per kasus (guard pointLedger exists)
     * — poin dipotong saat pertama masuk handling; transisi bolak-balik
     * ke handling tidak boleh memotong dua kali.
     */
    private function applyStatusChange(DisciplineCase $case, string $newStatus, ?string $notes = null): bool
    {
        $updates = ['status' => $newStatus];

        // Catatan per tahap — hanya ditulis bila user mengisi.
        $notesField = match ($newStatus) {
            'clarifying' => 'clarification_notes',
            'classifying' => 'classification_notes',
            'handling' => 'validation_notes',
            'monitoring' => 'handling_notes',
            'closed' => 'monitoring_notes',
            default => null,
        };
        if ($notesField && filled($notes)) {
            $updates[$notesField] = $notes;
        }

        if ($newStatus === 'handling') {
            $updates['validator_id'] = auth()->id();
            $updates['validated_at'] = now();
        }

        if ($newStatus === 'closed') {
            $updates['closed_at'] = now();
            $updates['closed_by'] = auth()->id();
        }

        $case->update($updates);

        if ($newStatus !== 'handling' || $case->pointLedger()->exists()) {
            return false;
        }

        $this->debitCase($case);

        return true;
    }

    /** Debit poin efektif kasus ke ledger (sekali seumur hidup kasus). */
    private function debitCase(DisciplineCase $case): void
    {
        $lastBalance = PointLedger::getLastBalance($case->student_id, $case->academic_year_id);

        PointLedger::create([
            'student_id' => $case->student_id,
            'academic_year_id' => $case->academic_year_id,
            'direction' => PointLedger::DIR_DEBIT,
            'amount' => $case->getEffectivePoints(),
            'balance_after' => $lastBalance - $case->getEffectivePoints(),
            'transaction_type' => PointLedger::TYPE_VIOLATION,
            'source_type' => DisciplineCase::class,
            'source_id' => $case->id,
            'reason' => 'Pelanggaran: ' . $case->violationCategory->code,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Update status kasus (alur ringkas: found → handling → closed).
     * Transisi ke 'handling' membuat debit poin ke ledger.
     */
    public function updateStatus(Request $request, DisciplineCase $disciplineCase)
    {
        $this->authorize('update', $disciplineCase);

        $validated = $request->validate([
            'status' => 'required|in:handling,closed,found,clarifying,classifying,monitoring',
            'notes' => 'nullable|string',
        ]);

        $newStatus = $validated['status'];

        if ($newStatus === 'handling') {
            // Transaksi atomik: update status + debit ledger tidak boleh terpisah.
            $debited = DB::transaction(
                fn () => $this->applyStatusChange($disciplineCase, 'handling', $validated['notes'] ?? null)
            );

            $message = $debited
                ? 'Kasus divalidasi, poin ' . $disciplineCase->getEffectivePoints() . ' berhasil dikurang.'
                : 'Kasus kembali ke penanganan — poin sudah dipotong sebelumnya.';

            return redirect()
                ->route('kasus-pelanggaran.show', $disciplineCase)
                ->with('success', $message);
        }

        $this->applyStatusChange($disciplineCase, $newStatus, $validated['notes'] ?? null);

        return redirect()
            ->route('kasus-pelanggaran.show', $disciplineCase)
            ->with('success', 'Kasus diperbarui ke status: ' . $newStatus);
    }

    /**
     * Ubah status massal dari index: pilih kasus → majukan sekali klik.
     *
     * Aturan: hanya MAJU ke tahap berikutnya/lebih depan (forward). Kasus yang
     * sudah berada di tahap tujuan atau lebih depan, dismissed, dan closed
     * dilewati — salah satunya mencegah debit ganda saat target handling.
     * Debit ledger tetap dibuat per kasus yang benar-benar masuk handling
     * (sekali, guard di applyStatusChange).
     */
    public function bulkStatus(Request $request)
    {
        $ids = $request->input('ids');
        $newStatus = $request->input('status');

        if (! is_array($ids) || $ids === []) {
            return redirect()->back()->with('error', 'Pilih minimal satu kasus terlebih dahulu.');
        }

        $allowed = ['handling', 'closed', 'clarifying', 'classifying', 'monitoring'];
        if (! in_array($newStatus, $allowed, true)) {
            return redirect()->back()->with('error', 'Tahap tujuan tidak dikenal.');
        }

        // Urutan tahap — kasus hanya boleh bergerak ke depan (alur ringkas: found -> handling -> closed).
        $order = [
            'found' => 0,
            'clarifying' => 0,
            'classifying' => 0,
            'handling' => 1,
            'monitoring' => 1,
            'closed' => 2,
        ];
        $target = $order[$newStatus] ?? 0;

        $cases = DisciplineCase::with('violationCategory')->whereIn('id', $ids)->get();
        $advanceable = $cases->filter(fn ($case) => ($order[$case->status] ?? 99) < $target);
        $skipped = $cases->count() - $advanceable->count();

        if ($advanceable->isEmpty()) {
            return redirect()->back()->with('warning', 'Tidak ada kasus yang bisa dimajukan — semua sudah di tahap tujuan atau lebih depan.');
        }

        // Policy per kasus: role bk/kesiswaan/admin + status asal valid.
        foreach ($advanceable as $case) {
            $this->authorize('update', $case);
        }

        $deducted = 0;
        DB::transaction(function () use ($advanceable, $newStatus, &$deducted) {
            foreach ($advanceable as $case) {
                if ($this->applyStatusChange($case, $newStatus)) {
                    $deducted++;
                }
            }
        });

        $labels = [
            'handling' => 'Penanganan',
            'closed' => 'Selesai',
            'clarifying' => 'Klarifikasi',
            'classifying' => 'Klasifikasi',
            'monitoring' => 'Monitoring',
        ];

        $message = $advanceable->count() . ' kasus dimajukan ke ' . $labels[$newStatus] . '.';
        if ($deducted > 0) {
            $message .= ' ' . $deducted . ' debit poin dibuat.';
        }
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' kasus dilewati.';
        }

        return redirect()->back()->with('success', $message);
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
