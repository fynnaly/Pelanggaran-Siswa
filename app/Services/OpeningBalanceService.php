<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\PointLedger;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class OpeningBalanceService
{
    public function __construct()
    {
    }

    /**
     * Generate saldo awal buat 1 siswa.
     * Saldo awal = saldo terakhir dari tahun sebelumnya + 2000.
     * Return true jika berhasil dibuat, false jika sudah ada (idempotent).
     */
    public function generateForStudent(Student $student, AcademicYear $academicYear, int $createdBy): bool
    {
        try {
            return DB::transaction(function () use ($student, $academicYear, $createdBy) {
                $exists = PointLedger::where('student_id', $student->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('transaction_type', PointLedger::TYPE_OPENING)
                    ->exists();

                if ($exists) {
                    return false;
                }

                $lastBalance = static::getLastBalance($student->id);
                                $newBalance = $lastBalance;

                PointLedger::create([
                    'student_id'       => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'direction'        => PointLedger::DIR_CREDIT,
                    'amount'           => PointLedger::OPENING_AMOUNT,
                    'balance_after'    => $newBalance,
                    'transaction_type' => PointLedger::TYPE_OPENING,
                    'source_type'      => null,
                    'source_id'        => null,
                    'reason'           => "Saldo awal tahun ajaran {$academicYear->name}",
                    'created_by'       => $createdBy,
                    'verified_by'      => $createdBy,
                ]);

                return true;
            });
        } catch (QueryException $error) {
            if ($error->getCode() === '23505' || str_contains($error->getMessage(), 'unique_opening_balance')) {
                return false;
            }
            throw $error;
        }
    }
}
