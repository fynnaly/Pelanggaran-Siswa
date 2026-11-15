<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ScopesWaliKelas;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AchievementRecord;
use App\Models\DisciplineCase;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\StudentDisciplineReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ApiReportController extends Controller
{
    use ScopesWaliKelas;

    private const STAFF_ROLES = ['admin', 'guru', 'wali_kelas', 'bk', 'kesiswaan', 'kepala_sekolah'];

    /**
     * Return a student's filtered discipline history as JSON.
     */
    public function student(Request $request, Student $student, StudentDisciplineReport $report): JsonResponse
    {
        $filters = $this->filters($request);
        $this->authorizeStudentReport($request->user(), $student, $filters['scope']);
        $this->authorizeSelectedClass($filters['class'], $filters['scope']);

        return response()->json([
            'data' => $this->serializeReport($report->forStudent($student, $filters)),
            'filters' => $this->publicFilters($filters),
        ]);
    }

    /**
     * Return a class's filtered discipline history grouped by student.
     */
    public function schoolClass(Request $request, SchoolClass $schoolClass, StudentDisciplineReport $report): JsonResponse
    {
        $this->authorizeStaff($request->user());
        $filters = $this->filters($request);
        $this->authorizeSelectedClass($schoolClass->id, $filters['scope']);
        $filters['class'] = $schoolClass->id;

        return response()->json([
            'data' => $this->serializeReport($report->forClass($schoolClass, $filters)),
            'class' => [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'academic_year' => $schoolClass->academicYear?->name,
            ],
            'filters' => $this->publicFilters($filters),
        ]);
    }

    /**
     * Validate report filters using the same defaults as the web reports.
     *
     * @return array{year: int|null, class: int|null, from: string|null, to: string|null, scope: array<int, int>|null}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'exists:academic_years,id'],
            'class' => ['nullable', 'integer', 'exists:classes,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'year' => $request->has('year')
                ? ($validated['year'] ?? null)
                : AcademicYear::where('is_active', true)->value('id'),
            'class' => isset($validated['class']) ? (int) $validated['class'] : null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'scope' => $this->waliKelasScope(),
        ];
    }

    /**
     * Restrict student reports to the owner or authorized staff.
     *
     * @param  array<int, int>|null  $scope
     */
    private function authorizeStudentReport(?User $user, Student $student, ?array $scope): void
    {
        if ($user?->hasRole('siswa') && ! $user->hasAnyRole(self::STAFF_ROLES)) {
            abort_unless(
                $user->student?->id === $student->id,
                403,
                'Akses ditolak. Anda hanya dapat melihat laporan milik sendiri.'
            );
        } else {
            $this->authorizeStaff($user);
        }

        if ($scope !== null) {
            $inScope = $student->enrollments()->whereIn('class_id', $scope)->exists()
                || $student->disciplineCases()->whereIn('class_id', $scope)->exists()
                || $student->achievementRecords()->whereIn('class_id', $scope)->exists();

            abort_unless($inScope, 403, 'Akses ditolak. Siswa ini tidak termasuk kelas binaan Anda.');
        }
    }

    /**
     * Ensure the user has a staff role.
     */
    private function authorizeStaff(?User $user): void
    {
        abort_unless($user?->hasAnyRole(self::STAFF_ROLES), 403, 'Akses ditolak. Fitur ini hanya untuk staf sekolah.');
    }

    /**
     * Ensure a selected class is within the current wali kelas scope.
     *
     * @param  array<int, int>|null  $scope
     */
    private function authorizeSelectedClass(?int $classId, ?array $scope): void
    {
        if ($scope !== null && $classId !== null) {
            abort_unless(in_array($classId, $scope, true), 403, 'Akses ditolak. Kelas ini tidak termasuk kelas binaan Anda.');
        }
    }

    /**
     * Serialize the shared report data without exposing unneeded model attributes.
     *
     * @param  array{students: Collection<int, array{student: Student, cases: Collection, achievements: Collection, ledger: Collection}>}  $reportData
     * @return array{students: array<int, array<string, mixed>>}
     */
    private function serializeReport(array $reportData): array
    {
        return [
            'students' => $reportData['students']->map(fn (array $group) => [
                'student' => [
                    'id' => $group['student']->id,
                    'nis' => $group['student']->nis,
                    'nisn' => $group['student']->nisn,
                    'name' => $group['student']->full_name,
                    'status' => $group['student']->status,
                ],
                'cases' => $group['cases']->map(fn (DisciplineCase $case) => [
                    'id' => $case->id,
                    'case_number' => $case->case_number,
                    'incident_date' => $case->incident_date?->toDateString(),
                    'category' => $case->violationCategory?->name,
                    'category_parent' => $case->violationCategory?->parent?->name,
                    'status' => $case->status,
                    'description' => $case->description,
                    'class' => $case->schoolClass?->name,
                    'academic_year' => $case->academicYear?->name,
                    'reported_by' => $case->reporter?->name,
                    'validated_by' => $case->validator?->name,
                ])->values(),
                'achievements' => $group['achievements']->map(fn (AchievementRecord $achievement) => [
                    'id' => $achievement->id,
                    'event_name' => $achievement->event_name,
                    'event_date' => $achievement->event_date?->toDateString(),
                    'category' => $achievement->achievementCategory?->name,
                    'category_parent' => $achievement->achievementCategory?->parent?->name,
                    'status' => $achievement->status,
                    'points_awarded' => (int) $achievement->points_awarded,
                    'description' => $achievement->description,
                    'discipline_case' => $achievement->disciplineCase ? [
                        'id' => $achievement->disciplineCase->id,
                        'case_number' => $achievement->disciplineCase->case_number,
                    ] : null,
                    'class' => $achievement->schoolClass?->name,
                    'academic_year' => $achievement->academicYear?->name,
                    'evidence_url' => $achievement->evidence_path
                        ? Storage::disk('public')->url($achievement->evidence_path)
                        : null,
                    'recorded_by' => $achievement->recorder?->name,
                    'verified_by' => $achievement->verifier?->name,
                ])->values(),
                'point_ledger' => $group['ledger']->map(fn (PointLedger $entry) => [
                    'id' => $entry->id,
                    'date' => $entry->created_at?->toIso8601String(),
                    'direction' => $entry->direction,
                    'amount' => (int) $entry->amount,
                    'balance_after' => (int) $entry->balance_after,
                    'transaction_type' => $entry->transaction_type,
                    'reason' => $entry->reason,
                    'source' => $this->ledgerSource($entry),
                ])->values(),
            ])->values()->all(),
        ];
    }

    /**
     * Return the case or achievement reference for a ledger transaction.
     *
     * @return array<string, mixed>|null
     */
    private function ledgerSource(PointLedger $entry): ?array
    {
        if ($entry->source instanceof DisciplineCase) {
            return [
                'type' => 'discipline_case',
                'id' => $entry->source->id,
                'case_number' => $entry->source->case_number,
            ];
        }

        if ($entry->source instanceof AchievementRecord) {
            return [
                'type' => 'achievement',
                'id' => $entry->source->id,
                'event_name' => $entry->source->event_name,
                'case_number' => $entry->source->disciplineCase?->case_number,
            ];
        }

        return null;
    }

    /**
     * Remove internal scope details from the filters returned to the client.
     *
     * @param  array{year: int|null, class: int|null, from: string|null, to: string|null, scope: array<int, int>|null}  $filters
     * @return array{year: int|null, class: int|null, from: string|null, to: string|null}
     */
    private function publicFilters(array $filters): array
    {
        return [
            'year' => $filters['year'],
            'class' => $filters['class'],
            'from' => $filters['from'],
            'to' => $filters['to'],
        ];
    }
}
