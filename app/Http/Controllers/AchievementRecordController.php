<?php

namespace App\Http\Controllers;

use App\Models\AchievementCategory;
use App\Models\AchievementRecord;
use App\Models\Student;
use App\Models\PointLedger;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AchievementRecordController extends Controller
{
    public function index()
    {
        $status = request('status');
        $search = request('search');

        $query = AchievementRecord::with(['student.currentEnrollment.schoolClass', 'schoolClass', 'achievementCategory', 'recorder'])
            ->latest('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('student', fn ($q) => $q->where('full_name', 'like', "%{$search}%"));
        }

        $records = $query->paginate(20)->withQueryString();

        return view('achievement-records.index', compact('records', 'status', 'search'));
    }

    public function create()
    {
        $categories = AchievementCategory::where('status', 'active')->get();

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'achievement_category_id' => 'required|exists:achievement_categories,id',
            'event_name' => 'required|string|max:255',
            'event_date' => 'nullable|date',
            'event_period' => 'nullable|string|max:100',
            'achievement_level' => 'nullable|string|max:100',
            'points_awarded' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        // Snapshot konteks saat kejadian: ambil dari enrollment siswa pada tahun berjalan.
        $activeYear = AcademicYear::where('is_active', true)->first();
        $student = Student::with(['enrollments.schoolClass'])->findOrFail($validated['student_id']);
        $enrollment = $student->enrollments
            ->firstWhere('academic_year_id', $activeYear?->id)
            ?? $student->enrollments->firstWhere('status', 'active')
            ?? $student->enrollments->sortByDesc('id')->first();

        $academicYearId = $enrollment?->academic_year_id ?? $activeYear?->id;

        $record = DB::transaction(function () use ($validated, $academicYearId, $enrollment) {
            return AchievementRecord::create([
                'student_id' => $validated['student_id'],
                'enrollment_id' => $enrollment?->id,
                'class_id' => $enrollment?->class_id,
                'achievement_category_id' => $validated['achievement_category_id'],
                'academic_year_id' => $academicYearId,
                'recorded_by' => auth()->id(),
                'status' => 'pending',
                'event_name' => $validated['event_name'],
                'event_date' => $validated['event_date'] ?? null,
                'event_period' => $validated['event_period'] ?? null,
                'achievement_level' => $validated['achievement_level'] ?? null,
                'points_awarded' => $validated['points_awarded'],
                'description' => $validated['description'] ?? null,
            ]);
        });

        return redirect()
            ->route('achievement-records.show', $record)
            ->with('success', 'Rekam prestasi berhasil dicatat (status: pending).');
    }

    public function show(AchievementRecord $achievementRecord)
    {
        $achievementRecord->load(['student.currentEnrollment.schoolClass', 'enrollment.schoolClass', 'schoolClass', 'achievementCategory', 'recorder', 'verifier', 'pointLedger']);

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
