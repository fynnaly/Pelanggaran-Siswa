<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\AchievementRecord;
use App\Models\PointLedger;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AchievementRecordVerifier
{
    /**
     * Verify a pending achievement and append its point credit to the ledger.
     */
    public function verify(AchievementRecord $achievementRecord, User $verifier): void
    {
        DB::transaction(function () use ($achievementRecord, $verifier) {
            $record = AchievementRecord::query()
                ->lockForUpdate()
                ->findOrFail($achievementRecord->id);

            if ($record->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya rekam prestasi berstatus pending yang dapat diverifikasi.',
                ]);
            }

            Student::query()->lockForUpdate()->findOrFail($record->student_id);
            $academicYearId = $record->academic_year_id
                ?? $record->student?->latestEnrollment()?->academic_year_id
                ?? AcademicYear::where('is_active', true)->value('id');
            $points = (int) $record->points_awarded;
            $now = now();
            $balanceAfter = PointLedger::getLastBalance($record->student_id, $academicYearId) + $points;

            $record->update([
                'status' => 'verified',
                'verified_by' => $verifier->id,
                'verified_at' => $now,
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
                'reason' => 'Prestasi: '.($record->event_name ?? $record->achievementCategory?->code),
                'created_by' => $verifier->id,
                'verified_by' => $verifier->id,
                'verified_at' => $now,
            ]);
        });
    }
}
