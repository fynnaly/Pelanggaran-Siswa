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

        $query = AchievementRecord::with(['student.schoolClass', 'achievementCategory', 'recorder'])
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

        return view('achievement-records.create', compact('categories', 'selectedStudent'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'achievement_category_id' => 'required|exists:achievement_categories,id',
            'description' => 'nullable|string',
        ]);

        $record = DB::transaction(function () use ($validated) {
            return AchievementRecord::create([
                'student_id' => $validated['student_id'],
                'achievement_category_id' => $validated['achievement_category_id'],
                'recorded_by' => auth()->id(),
                'status' => 'pending',
                'description' => $validated['description'],
            ]);
        });

        return redirect()
            ->route('achievement-records.show', $record)
            ->with('success', 'Rekam prestasi berhasil dicatat (status: pending).');
    }

    public function show(AchievementRecord $achievementRecord)
    {
        $achievementRecord->load(['student.schoolClass', 'achievementCategory', 'recorder', 'verifier', 'pointLedger']);

        return view('achievement-records.show', compact('achievementRecord'));
    }

    public function validate(Request $request, AchievementRecord $achievementRecord)
    {
        if ($achievementRecord->status !== 'pending') {
            return redirect()
                ->route('achievement-records.index')
                ->with('error', 'Hanya rekam prestasi dengan status pending yang bisa divalidasi.');
        }

        $category = $achievementRecord->achievementCategory;
        $points = $category->points;

        DB::transaction(function () use ($achievementRecord, $points, $category) {
            $academicYearId = $achievementRecord->student?->schoolClass?->academic_year_id
                ?? AcademicYear::where('is_active', true)->first()?->id;

            $lastBalance = $achievementRecord->student->pointLedgers()
                ->lockForUpdate()
                ->latest('id')
                ->value('balance_after') ?? 2000;

            $balanceAfter = $lastBalance + $points;

            $achievementRecord->status = 'verified';
            $achievementRecord->verified_by = auth()->id();
            $achievementRecord->verified_at = now();
            $achievementRecord->save();

            $achievementRecord->student->pointLedgers()->create([
                'student_id' => $achievementRecord->student_id,
                'academic_year_id' => $academicYearId,
                'direction' => 'credit',
                'amount' => $points,
                'balance_after' => $balanceAfter,
                'transaction_type' => PointLedger::TYPE_ACHIEVEMENT,
                'source_type' => AchievementRecord::class,
                'source_id' => $achievementRecord->id,
                'reason' => 'Prestasi: ' . $category->code,
                'created_by' => auth()->id(),
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        });

        return redirect()
            ->route('achievement-records.index')
            ->with('success', 'Rekam prestasi diverifikasi, poin +' . $points . ' berhasil ditambahkan.');
    }

    public function dismiss(AchievementRecord $achievementRecord)
    {
        if ($achievementRecord->status !== 'pending') {
            return redirect()
                ->route('achievement-records.index')
                ->with('error', 'Hanya rekam prestasi dengan status pending yang bisa ditolak.');
        }

        $achievementRecord->status = 'rejected';
        $achievementRecord->save();

        return redirect()
            ->route('achievement-records.index')
            ->with('warning', 'Rekam prestasi ditolak, poin tidak ditambahkan.');
    }
}
