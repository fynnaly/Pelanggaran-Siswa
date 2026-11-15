<?php

namespace App\Support;

use App\Models\AchievementRecord;
use App\Models\DisciplineCase;
use App\Models\Enrollment;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class StudentDisciplineReport
{
    /**
     * Build a report for one student using case and enrollment class snapshots.
     *
     * @param  array{year: int|null, class: int|null, from: string|null, to: string|null, scope: array<int, int>|null}  $filters
     * @return array{students: Collection<int, array{student: Student, cases: Collection, achievements: Collection, ledger: Collection}>}
     */
    public function forStudent(Student $student, array $filters): array
    {
        return $this->build([$student->id], $filters);
    }

    /**
     * Build one grouped history section for each student in a class snapshot.
     *
     * @param  array{year: int|null, class: int|null, from: string|null, to: string|null, scope: array<int, int>|null}  $filters
     * @return array{students: Collection<int, array{student: Student, cases: Collection, achievements: Collection, ledger: Collection}>}
     */
    public function forClass(SchoolClass $schoolClass, array $filters): array
    {
        $filters['class'] = $schoolClass->id;

        $studentIds = Enrollment::query()
            ->where('class_id', $schoolClass->id)
            ->when($filters['year'] !== null, fn (Builder $query) => $query->where('academic_year_id', $filters['year']))
            ->pluck('student_id')
            ->merge($this->caseQuery($filters)->where('class_id', $schoolClass->id)->pluck('student_id'))
            ->merge($this->achievementQuery($filters)->where('class_id', $schoolClass->id)->pluck('student_id'))
            ->unique()
            ->values()
            ->all();

        return $this->build($studentIds, $filters);
    }

    /**
     * @param  array<int, int>  $studentIds
     * @param  array{year: int|null, class: int|null, from: string|null, to: string|null, scope: array<int, int>|null}  $filters
     * @return array{students: Collection<int, array{student: Student, cases: Collection, achievements: Collection, ledger: Collection}>}
     */
    private function build(array $studentIds, array $filters): array
    {
        if ($studentIds === []) {
            return ['students' => collect()];
        }

        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->orderBy('full_name')
            ->get()
            ->keyBy('id');

        $cases = $this->caseQuery($filters)
            ->whereIn('student_id', $students->keys())
            ->with(['violationCategory.parent', 'schoolClass', 'academicYear', 'reporter', 'validator'])
            ->orderByRaw('COALESCE(incident_date, DATE(created_at)) DESC')
            ->get()
            ->groupBy('student_id');

        $achievements = $this->achievementQuery($filters)
            ->whereIn('student_id', $students->keys())
            ->with(['achievementCategory.parent', 'disciplineCase', 'schoolClass', 'academicYear', 'recorder', 'verifier'])
            ->orderByRaw('COALESCE(event_date, DATE(created_at)) DESC')
            ->get()
            ->groupBy('student_id');

        $ledgerQuery = PointLedger::query()
            ->whereIn('student_id', $students->keys())
            ->when($filters['year'] !== null, fn (Builder $query) => $query->where('academic_year_id', $filters['year']))
            ->when($filters['from'], fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->with([
                'academicYear',
                'creator',
                'verifier',
                'source' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    DisciplineCase::class => ['schoolClass', 'academicYear', 'violationCategory', 'validator'],
                    AchievementRecord::class => ['schoolClass', 'academicYear', 'achievementCategory', 'disciplineCase'],
                ]),
            ]);

        if ($filters['scope'] !== null) {
            $ledgerQuery->where(function (Builder $query) use ($filters) {
                $query->where(function (Builder $caseQuery) use ($filters) {
                    $caseQuery->where('source_type', DisciplineCase::class)
                        ->whereIn('source_id', $this->caseQuery($filters)->whereIn('class_id', $filters['scope'])->select('id'));
                })->orWhere(function (Builder $achievementQuery) use ($filters) {
                    $achievementQuery->where('source_type', AchievementRecord::class)
                        ->whereIn('source_id', $this->achievementQuery($filters)->whereIn('class_id', $filters['scope'])->select('id'));
                })->orWhere(function (Builder $openingQuery) use ($filters) {
                    $openingQuery->where('transaction_type', PointLedger::TYPE_OPENING)
                        ->whereIn('student_id', Enrollment::query()
                            ->whereIn('class_id', $filters['scope'])
                            ->when($filters['class'] !== null, fn (Builder $query) => $query->where('class_id', $filters['class']))
                            ->when($filters['year'] !== null, fn (Builder $query) => $query->where('academic_year_id', $filters['year']))
                            ->select('student_id'));
                });
            });
        } elseif ($filters['class'] !== null) {
            $classYearId = SchoolClass::whereKey($filters['class'])->value('academic_year_id');
            $caseIds = $this->caseQuery($filters)->where('class_id', $filters['class'])->select('id');
            $achievementIds = $this->achievementQuery($filters)->where('class_id', $filters['class'])->select('id');

            $ledgerQuery->where(function (Builder $query) use ($caseIds, $achievementIds, $classYearId) {
                $query->where(function (Builder $caseQuery) use ($caseIds) {
                    $caseQuery->where('source_type', DisciplineCase::class)->whereIn('source_id', $caseIds);
                })->orWhere(function (Builder $achievementQuery) use ($achievementIds) {
                    $achievementQuery->where('source_type', AchievementRecord::class)->whereIn('source_id', $achievementIds);
                })->orWhere(function (Builder $openingQuery) use ($classYearId) {
                    $openingQuery->where('transaction_type', PointLedger::TYPE_OPENING);
                    if ($classYearId !== null) {
                        $openingQuery->where('academic_year_id', $classYearId);
                    }
                });
            });
        }

        $ledger = $ledgerQuery->latest('created_at')->get()->groupBy('student_id');

        return [
            'students' => $students->map(fn (Student $student) => [
                'student' => $student,
                'cases' => $cases->get($student->id, collect()),
                'achievements' => $achievements->get($student->id, collect()),
                'ledger' => $ledger->get($student->id, collect()),
            ])->values(),
        ];
    }

    /**
     * @param  array{year: int|null, class: int|null, from: string|null, to: string|null, scope: array<int, int>|null}  $filters
     */
    private function caseQuery(array $filters): Builder
    {
        return DisciplineCase::query()
            ->when($filters['year'] !== null, fn (Builder $query) => $query->where('academic_year_id', $filters['year']))
            ->when($filters['class'] !== null, fn (Builder $query) => $query->where('class_id', $filters['class']))
            ->when($filters['scope'] !== null, fn (Builder $query) => $query->whereIn('class_id', $filters['scope']))
            ->when($filters['from'], fn (Builder $query) => $query->whereRaw('DATE(COALESCE(incident_date, created_at)) >= ?', [$filters['from']]))
            ->when($filters['to'], fn (Builder $query) => $query->whereRaw('DATE(COALESCE(incident_date, created_at)) <= ?', [$filters['to']]));
    }

    /**
     * @param  array{year: int|null, class: int|null, from: string|null, to: string|null, scope: array<int, int>|null}  $filters
     */
    private function achievementQuery(array $filters): Builder
    {
        return AchievementRecord::query()
            ->when($filters['year'] !== null, fn (Builder $query) => $query->where('academic_year_id', $filters['year']))
            ->when($filters['class'] !== null, fn (Builder $query) => $query->where('class_id', $filters['class']))
            ->when($filters['scope'] !== null, fn (Builder $query) => $query->whereIn('class_id', $filters['scope']))
            ->when($filters['from'], fn (Builder $query) => $query->whereRaw('DATE(COALESCE(event_date, created_at)) >= ?', [$filters['from']]))
            ->when($filters['to'], fn (Builder $query) => $query->whereRaw('DATE(COALESCE(event_date, created_at)) <= ?', [$filters['to']]));
    }
}
