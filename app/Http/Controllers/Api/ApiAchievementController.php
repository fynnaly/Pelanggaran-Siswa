<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AchievementCategory;
use App\Models\AchievementRecord;
use App\Models\DisciplineCase;
use App\Models\Student;
use App\Models\User;
use App\Support\AchievementRecordVerifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApiAchievementController extends Controller
{
    use ScopesWaliKelas;

    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    /**
     * List active achievement subcategories.
     */
    public function categories(Request $request): JsonResponse
    {
        $this->authorizeRegisteredUser($request);

        $categories = AchievementCategory::query()
            ->whereNotNull('parent_id')
            ->where('status', 'active')
            ->where(fn (Builder $query) => $query->whereNull('effective_date')->orWhereDate('effective_date', '<=', today()))
            ->where(fn (Builder $query) => $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()))
            ->with('parent:id,name')
            ->orderBy('code')
            ->get(['id', 'parent_id', 'code', 'name', 'points', 'description']);

        return response()->json([
            'data' => $categories->map(fn (AchievementCategory $category) => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'parent_name' => $category->parent?->name,
                'code' => $category->code,
                'name' => $category->name,
                'points' => (int) $category->points,
                'description' => $category->description,
            ])->values(),
        ]);
    }

    /**
     * List achievement records visible to the authenticated user for one student.
     */
    public function index(Request $request, Student $student): JsonResponse
    {
        $this->authorizeStudentAccess($request, $student);

        $records = AchievementRecord::query()
            ->where('student_id', $student->id)
            ->with(['achievementCategory.parent', 'disciplineCase', 'schoolClass', 'academicYear', 'recorder', 'verifier'])
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when(($scope = $this->waliKelasScope()) !== null, fn (Builder $query) => $query
                ->where(fn (Builder $visible) => $visible->whereIn('class_id', $scope)->orWhere('recorded_by', $request->user()->id)))
            ->latest('created_at')
            ->paginate(min(max($request->integer('limit', 20), 1), 100));

        return response()->json([
            'data' => $records->getCollection()->map(fn (AchievementRecord $record) => $this->recordPayload($record))->values(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    /**
     * Record an achievement for one student.
     */
    public function store(Request $request, AchievementRecordVerifier $verifier): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->hasAnyRole(self::STAFF_ROLES), 403, 'Akses ditolak. Fitur ini hanya untuk staf sekolah.');

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'achievement_category_id' => ['required', 'integer', 'exists:achievement_categories,id'],
            'discipline_case_id' => ['nullable', 'integer', 'exists:discipline_cases,id'],
            'event_name' => ['required', 'string', 'max:150'],
            'event_date' => ['nullable', 'date'],
            'achievement_level' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $record = DB::transaction(function () use ($validated, $user): AchievementRecord {
            $student = Student::query()
                ->with(['enrollments.schoolClass'])
                ->lockForUpdate()
                ->findOrFail($validated['student_id']);
            $category = AchievementCategory::query()
                ->lockForUpdate()
                ->findOrFail($validated['achievement_category_id']);

            if ($student->status !== 'active') {
                throw ValidationException::withMessages(['student_id' => 'Siswa tidak berstatus aktif.']);
            }

            if (
                $category->parent_id === null
                || $category->status !== 'active'
                || ($category->effective_date && $category->effective_date->gt(today()))
                || ($category->expiry_date && $category->expiry_date->lt(today()))
            ) {
                throw ValidationException::withMessages(['achievement_category_id' => 'Pilih sub-kategori prestasi yang masih aktif.']);
            }

            $this->authorizeStudentScope($student, $user);

            $activeYear = AcademicYear::where('is_active', true)->first();
            $enrollment = $student->enrollments
                ->firstWhere('academic_year_id', $activeYear?->id)
                ?? $student->enrollments->firstWhere('status', 'active')
                ?? $student->enrollments->sortByDesc('id')->first();

            $case = null;
            if (! empty($validated['discipline_case_id'])) {
                $case = DisciplineCase::query()
                    ->with('violationCategory.parent')
                    ->lockForUpdate()
                    ->findOrFail($validated['discipline_case_id']);

                if (
                    (int) $case->student_id !== (int) $student->id
                    || in_array($case->status, ['closed', 'dismissed', 'archived'], true)
                ) {
                    throw ValidationException::withMessages([
                        'discipline_case_id' => 'Kasus tidak aktif atau bukan milik siswa yang dipilih.',
                    ]);
                }

                if (AchievementRecord::where('discipline_case_id', $case->id)->exists()) {
                    throw ValidationException::withMessages([
                        'discipline_case_id' => 'Kasus ini sudah memiliki rekam prestasi tertaut.',
                    ]);
                }

                $isMappedCategory = collect($case->violationCategory?->recoveryOptions() ?? [])
                    ->contains(fn (array $option) => (int) $option['id'] === (int) $category->id);

                if (! $isMappedCategory) {
                    throw ValidationException::withMessages([
                        'achievement_category_id' => 'Kategori prestasi tidak dipadankan dengan kategori pelanggaran pada kasus ini.',
                    ]);
                }
            }

            return AchievementRecord::create([
                'student_id' => $student->id,
                'enrollment_id' => $enrollment?->id,
                'class_id' => $enrollment?->class_id,
                'achievement_category_id' => $category->id,
                'discipline_case_id' => $case?->id,
                'academic_year_id' => $enrollment?->academic_year_id ?? $activeYear?->id,
                'recorded_by' => $user->id,
                'status' => 'pending',
                'event_name' => $validated['event_name'],
                'event_date' => $validated['event_date'] ?? null,
                'achievement_level' => $validated['achievement_level'] ?? null,
                'points_awarded' => $category->points,
                'description' => $validated['description'] ?? null,
            ]);
        });

        if ($user->hasAnyRole(['bk', 'kesiswaan'])) {
            $verifier->verify($record, $user);
            $record->refresh();
        }

        return response()->json([
            'message' => $record->status === 'verified'
                ? 'Rekam prestasi dicatat dan langsung diverifikasi.'
                : 'Rekam prestasi berhasil dicatat dengan status pending.',
            'data' => $this->recordPayload($record->load([
                'achievementCategory.parent', 'disciplineCase', 'schoolClass', 'academicYear', 'recorder', 'verifier',
            ])),
        ], 201);
    }

    /**
     * Ensure an authenticated user has an application role.
     */
    private function authorizeRegisteredUser(Request $request): void
    {
        abort_unless(
            $request->user()?->hasAnyRole(array_merge(self::STAFF_ROLES, ['siswa'])),
            403,
            'Akses ditolak. Fitur ini hanya untuk pengguna terdaftar.'
        );
    }

    /**
     * Ensure a student may access the requested student's records.
     */
    private function authorizeStudentAccess(Request $request, Student $student): void
    {
        $this->authorizeRegisteredUser($request);
        $user = $request->user();

        if ($user->hasRole('siswa') && ! $user->hasAnyRole(self::STAFF_ROLES)) {
            abort_unless(
                $user->student?->id === $student->id,
                403,
                'Akses ditolak. Anda hanya boleh melihat data prestasi Anda sendiri.'
            );
        }

        $scope = $this->waliKelasScope();
        if ($scope !== null) {
            $inScope = $student->enrollments()->whereIn('class_id', $scope)->exists()
                || $student->achievementRecords()->whereIn('class_id', $scope)->exists()
                || $student->achievementRecords()->where('recorded_by', $user->id)->exists();

            abort_unless($inScope, 403, 'Siswa di luar kelas yang Anda ampu.');
        }
    }

    /**
     * Ensure a staff user may record an achievement for the selected student.
     */
    private function authorizeStudentScope(Student $student, User $user): void
    {
        $scope = $this->waliKelasScope();
        if ($scope !== null) {
            abort_unless(
                $student->enrollments()->whereIn('class_id', $scope)->exists(),
                403,
                'Siswa di luar kelas yang Anda ampu.'
            );
        }
    }

    /**
     * Build the public fields for an achievement record.
     *
     * @return array<string, mixed>
     */
    private function recordPayload(AchievementRecord $record): array
    {
        return [
            'id' => $record->id,
            'student_id' => $record->student_id,
            'event_name' => $record->event_name,
            'event_date' => $record->event_date?->toDateString(),
            'achievement_level' => $record->achievement_level,
            'description' => $record->description,
            'status' => $record->status,
            'points_awarded' => (int) $record->points_awarded,
            'category' => $record->achievementCategory ? [
                'id' => $record->achievementCategory->id,
                'code' => $record->achievementCategory->code,
                'name' => $record->achievementCategory->name,
                'parent_name' => $record->achievementCategory->parent?->name,
            ] : null,
            'discipline_case' => $record->disciplineCase ? [
                'id' => $record->disciplineCase->id,
                'case_number' => $record->disciplineCase->case_number,
            ] : null,
            'class' => $record->schoolClass?->name,
            'academic_year' => $record->academicYear?->name,
            'recorded_by' => $record->recorder?->name,
            'verified_by' => $record->verifier?->name,
            'verified_at' => $record->verified_at?->toIso8601String(),
            'evidence_path' => $record->evidence_path,
        ];
    }
}
