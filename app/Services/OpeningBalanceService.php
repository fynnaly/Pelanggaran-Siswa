<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\PointLedger;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class OpeningBalanceService
{

    /**
     * * NOTE: Membuat/Generate saldo awal buat 1 siswa
     * * Lalu, mengembalikan kondisi 'true' jika berhasil dibuat
     * * kondisi 'false' jika sudah ada (idempotent)
     */

    public function generateForStudent(Student $student, AcademicYear $academicYear, int $createdBy) {
        try {
            return DB::transaction(function () use ($student, $academicYear, $createdBy) {

            $exists = PointLedger::where('student_id', $student->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('transaction_type', PointLedger::TYPE_OPENING)
                    ->exists();

                if ($exists) {
                    return false;
                }

                // Memasuki Ledger
                PointLedger::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'direction' => PointLedger::DIR_CREDIT,
                    'amount' => PointLedger::OPENING_AMOUNT,
                    'balance_after' => PointLedger::OPENING_AMOUNT,
                    'transaction_type' => PointLedger::TYPE_OPENING,
                    'source_type' => null,
                    'source_id' => null,
                    'reason' => "Saldo awal tahun ajaran {$academicYear->name}",
                    'created_by' => $createdBy,
                    'verified_by' => $createdBy,
                ]);

                return true;
            });
        } catch (QueryException $error) {
            // Nanganin race condition (Concurrent requests)
            // postgreSql error code 23505 adalah unique_violation
            if ($error->getCode() === '23505' || str_contains($error->getMessage(), 'unique_opening_balance')) {
                // Gagal karna dublikat di DB, jadi basicly anggap aja sudah ada (idempotent)
                return false;
            }
            throw $error;
        }
    }

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
}
