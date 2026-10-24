<?php

namespace App\Http\Controllers;

use App\Models\AchievementCategory;
use App\Models\AchievementRecord;
use App\Models\AcademicYear;
use App\Models\DisciplineCase;
use App\Models\Student;
use App\Models\PointLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AchievementRecordController extends Controller
{
    public function index()
    {
        $status = request('status');
        $search = request('search');
        $sort = request()->string('sort')->toString();
        $order = strtolower(request()->string('order')->toString()) === 'asc' ? 'asc' : 'desc';
        $allowed = ['id', 'created_at', 'status', 'student_id', 'achievement_category_id'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'created_at';

        $query = AchievementRecord::with(['student.currentEnrollment.schoolClass', 'schoolClass', 'achievementCategory', 'disciplineCase.violationCategory', 'recorder']);

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
        // Hanya sub-kategori (punya induk) — kategori induk berpoin 0 tidak dipilih
        // saat mencatat prestasi.
        $categories = AchievementCategory::where('status', 'active')
            ->whereNotNull('parent_id')
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

        return view('achievement-records.create', compact('categories', 'selectedStudent'));
    }

    /**
     * JSON kasus pelanggaran siswa yang belum ditebus — untuk dropdown
     * "Tautkan ke Kasus Pelanggaran" di form create (deteksi otomatis
     * apakah siswa ini pernah melanggar).
     */
    public function openCases(Student $student)
    {
        // Dilindungi middleware 'auth' sama seperti index/create/store — tidak
        // memanggil authorize() di sini supaya konsisten dengan metode lain.
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
        // Batas validasi = panjang kolom DB (VARCHAR 150/20/50) — sinkron dengan
        // migration sync_schema_phase2, supaya validasi lolos tapi SQL gagal tak terjadi.
        //
        // TIDAK ada input points_awarded / event_period: poin SUDAH melekat di
        // kategori prestasi (achievement_categories.points) — kolom lama tetap ada
        // untuk data historis, tapi form tidak lagi menanyakannya.
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'achievement_category_id' => 'required|exists:achievement_categories,id',
            'discipline_case_id' => 'nullable|exists:discipline_cases,id',
            'event_name' => 'required|string|max:150',
            'event_date' => 'nullable|date',
            'achievement_level' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $category = AchievementCategory::findOrFail($validated['achievement_category_id']);

        // Snapshot konteks saat kejadian: ambil dari enrollment siswa pada tahun berjalan.
        $activeYear = AcademicYear::where('is_active', true)->first();
        $student = Student::with(['enrollments.schoolClass'])->findOrFail($validated['student_id']);
        $enrollment = $student->enrollments
            ->firstWhere('academic_year_id', $activeYear?->id)
            ?? $student->enrollments->firstWhere('status', 'active')
            ?? $student->enrollments->sortByDesc('id')->first();

        $academicYearId = $enrollment?->academic_year_id ?? $activeYear?->id;

        // Kaitan ke kasus wajib bila siswa punya kasus terbuka dan form memilih
        // kasus; kalau kasus dipilih tapi tidak cocok dengan siswa → tolak (cegah
        // prestasi orang lain menutup kasus siswa lain).
        $caseId = $validated['discipline_case_id'] ?? null;
        if ($caseId !== null) {
            $ownsCase = DisciplineCase::whereKey($caseId)
                ->where('student_id', $student->id)
                ->whereNotIn('status', ['closed', 'dismissed'])
                ->exists();
            abort_unless($ownsCase, 422, 'Kasus pelanggaran tidak valid untuk siswa ini.');
        }

        $record = DB::transaction(function () use ($validated, $academicYearId, $enrollment, $category) {
            return AchievementRecord::create([
                'student_id' => $validated['student_id'],
                'enrollment_id' => $enrollment?->id,
                'class_id' => $enrollment?->class_id,
                'achievement_category_id' => $validated['achievement_category_id'],
                'discipline_case_id' => $validated['discipline_case_id'] ?? null,
                'academic_year_id' => $academicYearId,
                'recorded_by' => auth()->id(),
                'status' => 'pending',
                'event_name' => $validated['event_name'],
                'event_date' => $validated['event_date'] ?? null,
                'achievement_level' => $validated['achievement_level'] ?? null,
                // Poin dari kategori — bukan input manual (satu sumber kebenaran).
                'points_awarded' => $category->points,
                'description' => $validated['description'] ?? null,
            ]);
        });

        $pesan = $record->isRedemption()
            ? 'Rekam prestasi dicatat sebagai penebusan kasus ' . $record->disciplineCase?->case_number . ' (status: pending).'
            : 'Rekam prestasi berhasil dicatat (status: pending).';

        return redirect()
            ->route('achievement-records.show', $record)
            ->with('success', $pesan);
    }

    public function show(AchievementRecord $achievementRecord)
    {
        $achievementRecord->load(['student.currentEnrollment.schoolClass', 'enrollment.schoolClass', 'schoolClass', 'achievementCategory', 'disciplineCase.violationCategory', 'recorder', 'verifier', 'pointLedger']);

        return view('achievement-records.show', compact('achievementRecord'));
    }

    public function validate(Request $request, AchievementRecord $achievementRecord)
    {
        if ($achievementRecord->status !== 'pending') {
            return redirect()
                ->route('achievement-records.index')
                ->with('error', 'Hanya rekam prestasi dengan status pending yang bisa divalidasi.');
        }

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

        return redirect()
            ->route('achievement-records.index')
            ->with('success', 'Rekam prestasi diverifikasi, poin +' . $points . ' berhasil ditambahkan.');
    }

    public function dismiss(Request $request, AchievementRecord $achievementRecord)
    {
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
}
