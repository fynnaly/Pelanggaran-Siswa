<?php

namespace App\Http\Controllers;

use App\Models\AchievementCategory;
use App\Models\AchievementRecord;
use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AchievementRecordController extends Controller
{
    use \App\Http\Controllers\Concerns\ScopesWaliKelas;

    /** Daftar role yang dianggap staf sekolah. User tanpa role apa pun = DITOLAK. */
    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    private function authorizeStaff(): void
    {
        abort_unless(
            request()->user()?->hasAnyRole(self::STAFF_ROLES),
            403,
            'Akses ditolak. Fitur ini hanya untuk staf sekolah.'
        );
    }

    public function index()
    {
        $status = request('status');
        $search = request('search');
        $sort = request()->string('sort')->toString();
        $order = strtolower(request()->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'created_at', 'status', 'student_id', 'achievement_category_id'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'created_at';

        $query = AchievementRecord::with(['student.currentEnrollment.schoolClass', 'schoolClass', 'achievementCategory', 'disciplineCase.violationCategory', 'recorder']);

        $user = auth()->user();
        if ($user && $user->hasRole('siswa') && ! $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'])) {
            $studentId = $user->student?->id;
            abort_unless($studentId, 403, 'Akses ditolak. Akun siswa Anda belum terhubung dengan data siswa.');
            $query->where('student_id', $studentId);
        }

        // Scope wali kelas: pencapaian siswa di kelas binaan + prestasi yang ia rekam
        // sendiri (guru non-wali tetap bisa melacak status validasinya).
        $waliScope = $this->waliKelasScope();
        if ($waliScope !== null) {
            $query->where(function ($q) use ($waliScope, $user) {
                $q->whereHas(
                    'student.currentEnrollment',
                    fn ($eq) => $eq->whereIn('class_id', $waliScope)
                )->orWhere('recorded_by', $user->id);
            });
        }

        if ($sort === 'created_at') {
            $query->latest('created_at');
        } else {
            $query->orderBy($sort, $order);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('student', fn ($q) => $q->where('full_name', 'like', "%{$search}%"));
        }

        $records = $query->paginate(20)->withQueryString();

        return view('achievement-records.index', compact('records', 'status', 'search', 'sort', 'order'));
    }

    public function create()
    {
        $this->authorizeStaff();

        // Hanya sub-kategori (punya induk) — kategori induk berpoin 0 tidak dipilih
        // saat mencatat prestasi.
        $categories = AchievementCategory::where('status', 'active')
            ->whereNotNull('parent_id')
            ->get();

        $classes = SchoolClass::where('academic_year_id', AcademicYear::where('is_active', true)->value('id'))
            ->orderBy('name')
            ->get();

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

        $activeYear = AcademicYear::where('is_active', true)->first();
        $hasActiveYear = (bool) $activeYear;
        $hasStudents = Student::where('status', 'active')->exists();
        $hasCategories = $categories->isNotEmpty();
        $hasPrerequisites = $hasActiveYear && $hasStudents && $hasCategories;

        $prerequisites = [
            [
                'label' => 'Tahun Ajaran',
                'ready' => $hasActiveYear,
                'url' => route('tahun-ajaran.create'),
                'action' => 'Buat Tahun Ajaran',
            ],
            [
                'label' => 'Siswa',
                'ready' => $hasStudents,
                'url' => route('students.create'),
                'action' => 'Tambah Siswa',
            ],
            [
                'label' => 'Kategori Prestasi',
                'ready' => $hasCategories,
                'url' => route('achievement-categories.create'),
                'action' => 'Tambah Kategori',
            ],
        ];

        return view('achievement-records.create', compact('categories', 'selectedStudent', 'classes', 'hasPrerequisites', 'prerequisites'));
    }

    /**
     * JSON kasus pelanggaran siswa yang belum ditebus — untuk dropdown
     * "Tautkan ke Kasus Pelanggaran" di form create (deteksi otomatis
     * apakah siswa ini pernah melanggar).
     */
    public function openCases(Student $student)
    {
        $this->authorizeStaff();

        $cases = DisciplineCase::with(['violationCategory', 'schoolClass'])
            ->where('student_id', $student->id)
            ->whereNotIn('status', ['closed', 'dismissed'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('achievement_records')
                    ->whereColumn('achievement_records.discipline_case_id', 'discipline_cases.id');
            })
            ->orderByDesc('incident_date')
            ->get()
            ->map(fn (DisciplineCase $c) => [
                'id' => $c->id,
                'label' => "{$c->case_number} — {$c->violationCategory?->name} (" . match ($c->status) {
                    'found' => 'Ditemukan',
                    'clarifying' => 'Klarifikasi',
                    'classifying' => 'Klasifikasi',
                    'handling' => 'Penanganan',
                    'monitoring' => 'Monitoring',
                    default => $c->status,
                } . ')',
                'required_achievement' => $c->violationCategory?->recoveryOptions() ?? [],
            ]);

        return response()->json($cases);
    }

    public function store(Request $request)
    {
        $this->authorizeStaff();

        // Batas validasi = panjang kolom DB (VARCHAR 150/20/50) — sinkron dengan
        // migration sync_schema_phase2, supaya validasi lolos tapi SQL gagal tak terjadi.
        //
        // TIDAK ada input points_awarded / event_period: poin SUDAH melekat di
        // kategori prestasi (achievement_categories.points) — kolom lama tetap ada
        // untuk data historis, tapi form tidak lagi menanyakannya.
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'achievement_category_id' => 'required|exists:achievement_categories,id',
            'discipline_case_id' => 'nullable|exists:discipline_cases,id',
            'event_name' => 'required|string|max:150',
            'event_date' => 'nullable|date',
            'achievement_level' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $studentIds = collect($validated['student_ids'] ?? [])
            ->push($validated['student_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return back()->withInput()->with('error', 'Pilih minimal satu siswa penerima prestasi.');
        }

        $category = AchievementCategory::findOrFail($validated['achievement_category_id']);
        $activeYear = AcademicYear::where('is_active', true)->first();
        $caseId = $validated['discipline_case_id'] ?? null;
        $createdRecords = [];

        // BK & Kesiswaan: rekam prestasi langsung diverifikasi (kredit poin),
        // tanpa tahap validasi terpisah.
        $autoVerify = (bool) auth()->user()?->hasAnyRole(['bk', 'kesiswaan']);

        DB::transaction(function () use ($studentIds, $validated, $category, $activeYear, $caseId, &$createdRecords) {
            foreach ($studentIds as $sid) {
                $student = Student::with(['enrollments.schoolClass'])->findOrFail($sid);
                $enrollment = $student->enrollments
                    ->firstWhere('academic_year_id', $activeYear?->id)
                    ?? $student->enrollments->firstWhere('status', 'active')
                    ?? $student->enrollments->sortByDesc('id')->first();

                $academicYearId = $enrollment?->academic_year_id ?? $activeYear?->id;

                // Tautkan kasus hanya jika input kasus cocok dengan siswa ini
                $linkedCaseId = null;
                if ($caseId !== null) {
                    $ownsCase = DisciplineCase::whereKey($caseId)
                        ->where('student_id', $student->id)
                        ->whereNotIn('status', ['closed', 'dismissed'])
                        ->exists();
                    if ($ownsCase) {
                        $linkedCaseId = $caseId;
                    }
                }

                $createdRecords[] = AchievementRecord::create([
                    'student_id' => $sid,
                    'enrollment_id' => $enrollment?->id,
                    'class_id' => $enrollment?->class_id,
                    'achievement_category_id' => $validated['achievement_category_id'],
                    'discipline_case_id' => $linkedCaseId,
                    'academic_year_id' => $academicYearId,
                    'recorded_by' => auth()->id(),
                    'status' => 'pending',
                    'event_name' => $validated['event_name'],
                    'event_date' => $validated['event_date'] ?? null,
                    'achievement_level' => $validated['achievement_level'] ?? null,
                    'points_awarded' => $category->points,
                    'description' => $validated['description'] ?? null,
                ]);
            }
        });

        if ($autoVerify) {
            foreach ($createdRecords as $record) {
                if ($record->status === 'pending') {
                    $this->verifyRecord($record);
                }
            }
        }

        $count = count($createdRecords);
        if ($count === 1) {
            $record = $createdRecords[0];
            if ($autoVerify) {
                $pesan = 'Rekam prestasi dicatat dan langsung diverifikasi (poin +' . number_format((int) $record->points_awarded) . ' ditambahkan).';
            } else {
                $pesan = $record->isRedemption()
                    ? 'Rekam prestasi dicatat sebagai penebusan kasus ' . $record->disciplineCase?->case_number . ' (status: pending).'
                    : 'Rekam prestasi berhasil dicatat (status: pending).';
            }

            return redirect()
                ->route('achievement-records.show', $record)
                ->with('success', $pesan);
        }

        $statusAkhir = $autoVerify ? 'langsung diverifikasi' : 'status: pending';
        return redirect()
            ->route('achievement-records.index')
            ->with('success', "{$count} catatan prestasi berhasil direkam secara kolektif ({$statusAkhir}).");
    }

    public function show(AchievementRecord $achievementRecord)
    {
        $user = auth()->user();
        if ($user && $user->hasRole('siswa') && ! $user->hasAnyRole(['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'])) {
            abort_unless($user->student?->id === $achievementRecord->student_id, 403, 'Akses ditolak. Anda hanya boleh melihat data prestasi Anda sendiri.');
        }

        // Scope wali kelas: boleh melihat jika siswa adalah anggota kelas binaan,
        // ATAU jika ia sendiri yang merekam prestasi tersebut.
        $waliScope = $this->waliKelasScope();
        if ($waliScope !== null) {
            $studentClassId = $achievementRecord->student?->currentEnrollment?->class_id ?? $achievementRecord->class_id;
            $isOwnClass = $studentClassId && in_array($studentClassId, $waliScope, true);
            $isRecorder = $achievementRecord->recorded_by === $user->id;
            abort_unless(
                $isOwnClass || $isRecorder,
                403,
                'Akses ditolak. Anda hanya dapat memantau prestasi siswa di kelas binaan atau yang Anda rekam sendiri.'
            );
        }

        $achievementRecord->load(['student.currentEnrollment.schoolClass', 'enrollment.schoolClass', 'schoolClass', 'achievementCategory', 'disciplineCase.violationCategory', 'recorder', 'verifier', 'pointLedger']);

        return view('achievement-records.show', compact('achievementRecord'));
    }

    private function authorizeVerifier(): void
    {
        if (! auth()->user()?->hasAnyRole(['bk', 'kesiswaan', 'admin', 'kepala_sekolah'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk memvalidasi prestasi.');
        }
    }

    public function validate(Request $request, AchievementRecord $achievementRecord)
    {
        $this->authorizeVerifier();

        if ($achievementRecord->status !== 'pending') {
            return redirect()
                ->route('achievement-records.index')
                ->with('error', 'Hanya rekam prestasi dengan status pending yang bisa divalidasi.');
        }

        $this->verifyRecord($achievementRecord);

        return redirect()
            ->route('achievement-records.index')
            ->with('success', 'Rekam prestasi diverifikasi, poin +' . $achievementRecord->points_awarded . ' berhasil ditambahkan.');
    }

    /**
     * Verifikasi satu rekam prestasi: pending → verified + kredit poin ke
     * ledger. DIPAKAI BERSAMA oleh validate() (manual) dan store() (otomatis
     * BK / Kesiswaan) supaya aturan kredit tidak terpecah dua.
     */
    private function verifyRecord(AchievementRecord $achievementRecord): void
    {
        $points = $achievementRecord->points_awarded;

        DB::transaction(function () use ($achievementRecord, $points) {
            $academicYearId = $achievementRecord->academic_year_id
                ?? $achievementRecord->student?->academic_year_id
                ?? AcademicYear::where('is_active', true)->first()?->id;

            $lastBalance = PointLedger::getLastBalance($achievementRecord->student_id, $academicYearId);

            $balanceAfter = $lastBalance + $points;

            $achievementRecord->update([
                'status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            PointLedger::create([
                'student_id' => $achievementRecord->student_id,
                'academic_year_id' => $academicYearId,
                'direction' => PointLedger::DIR_CREDIT,
                'amount' => $points,
                'balance_after' => $balanceAfter,
                'transaction_type' => PointLedger::TYPE_ACHIEVEMENT,
                'source_type' => AchievementRecord::class,
                'source_id' => $achievementRecord->id,
                'reason' => 'Prestasi: ' . ($achievementRecord->event_name ?? $achievementRecord->achievementCategory->code),
                'created_by' => auth()->id(),
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        });
    }

    public function dismiss(Request $request, AchievementRecord $achievementRecord)
    {
        $this->authorizeVerifier();

        if ($achievementRecord->status !== 'pending') {
            return redirect()
                ->route('achievement-records.index')
                ->with('error', 'Hanya rekam prestasi dengan status pending yang bisa ditolak.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $achievementRecord->update([
            'status' => 'rejected',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()
            ->route('achievement-records.index')
            ->with('warning', 'Rekam prestasi ditolak, poin tidak ditambahkan.');
    }

    public function bulkValidate(Request $request)
    {
        $this->authorizeVerifier();

        $ids = $request->input('ids');
        if (! is_array($ids) || $ids === []) {
            return redirect()->back()->with('error', 'Pilih minimal satu rekam prestasi terlebih dahulu.');
        }

        $records = AchievementRecord::with(['student', 'achievementCategory'])
            ->whereIn('id', $ids)
            ->where('status', 'pending')
            ->get();

        if ($records->isEmpty()) {
            return redirect()->back()->with('warning', 'Tidak ada rekam prestasi berstatus pending yang dapat divalidasi.');
        }

        $defaultAcademicYearId = AcademicYear::where('is_active', true)->first()?->id;
        $totalPoints = 0;

        DB::transaction(function () use ($records, $defaultAcademicYearId, &$totalPoints) {
            foreach ($records as $record) {
                $points = $record->points_awarded;
                $totalPoints += $points;

                $academicYearId = $record->academic_year_id
                    ?? $record->student?->academic_year_id
                    ?? $defaultAcademicYearId;

                $lastBalance = PointLedger::getLastBalance($record->student_id, $academicYearId);
                $balanceAfter = $lastBalance + $points;

                $record->update([
                    'status' => 'verified',
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);

                PointLedger::create([
                    'student_id' => $record->student_id,
                    'academic_year_id' => $academicYearId,
                    'direction' => PointLedger::DIR_CREDIT,
                    'amount' => $points,
                    'balance_after' => $balanceAfter,
                    'transaction_type' => PointLedger::TYPE_ACHIEVEMENT,
                    'source_type' => AchievementRecord::class,
                    'source_id' => $record->id,
                    'reason' => 'Prestasi: ' . ($record->event_name ?? $record->achievementCategory?->code),
                    'created_by' => auth()->id(),
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);
            }
        });

        $skipped = count($ids) - $records->count();
        $msg = $records->count() . ' rekam prestasi berhasil divalidasi (total +' . $totalPoints . ' poin).';
        if ($skipped > 0) {
            $msg .= ' (' . $skipped . ' dilewati karena bukan pending)';
        }

        return redirect()->back()->with('success', $msg);
    }
}
