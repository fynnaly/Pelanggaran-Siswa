<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\PointLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClassPromotionService
{
    /** Grade increment mapping: X→XI, XI→XII */
    protected const GRADE_MAP = [
        'X'  => 'XI',
        'XI' => 'XII',
    ];

    /**
     * Jalankan seluruh proses kenaikan kelas.
     * Dipanggil saat admin mengaktifkan tahun ajaran baru.
     *
     * Logika:
     * - X→XI, XI→XII: rename kelas in place, pindah ke tahun ajaran baru
     * - XII: siswa graduated, kelas TETAP (histori, tidak dihapus)
     *
     * @return array{classes_renamed: int, students_graduated: int, balances_created: int}
     */
    public function promote(AcademicYear $newYear): array
    {
        return DB::transaction(function () use ($newYear) {
            // 1. Cari tahun ajaran sebelumnya
            $previousYear = AcademicYear::where('id', '!=', $newYear->id)
                ->orderByDesc('start_date')
                ->first();

            if (!$previousYear) {
                return ['classes_renamed' => 0, 'students_graduated' => 0, 'balances_created' => 0];
            }

            // 2. Ambil semua kelas di tahun sebelumnya
            $oldClasses = SchoolClass::where('academic_year_id', $previousYear->id)->get();

            $classesRenamed = 0;
            $studentsGraduated = 0;

            foreach ($oldClasses as $oldClass) {
                $newGrade = $this->getNextGrade($oldClass->name);

                // XII lulus — siswa graduated, kelas TETAP (histori)
                if ($newGrade === null) {
                    $graduated = Student::where('class_id', $oldClass->id)
                        ->where('status', 'active')
                        ->update(['status' => 'graduated']);
                    $studentsGraduated += $graduated;
                    Log::info("Graduated: {$oldClass->name} → {$graduated} students graduated, class kept as history");
                    continue;
                }

                // Rename kelas + pindah ke tahun ajaran baru
                $newClassName = $this->replaceGrade($oldClass->name, $newGrade);
                $oldClass->update([
                    'name'               => $newClassName,
                    'academic_year_id'   => $newYear->id,
                ]);
                $classesRenamed++;

                Log::info("Renamed: {$oldClass->name} → {$newClassName}");
            }

            // 3. Generate saldo awal untuk semua siswa aktif (carry forward)
            $activeStudents = Student::where('status', 'active')->get();
            $balanceCreated = 0;

            foreach ($activeStudents as $student) {
                if ($this->createOpeningBalance($student, $newYear)) {
                    $balanceCreated++;
                }
            }

            // Bersihkan OPENING_BALANCE lama dari tahun sebelumnya
            PointLedger::where('transaction_type', PointLedger::TYPE_OPENING)
                ->where('academic_year_id', $previousYear->id)
                ->whereIn('student_id', $activeStudents->pluck('id'))
                ->delete();

            Log::info("Promotion complete: {$classesRenamed} classes renamed, {$studentsGraduated} graduated, {$balanceCreated} balances");

            return [
                'classes_renamed'    => $classesRenamed,
                'students_graduated' => $studentsGraduated,
                'balances_created'   => $balanceCreated,
            ];
        });
    }

    /**
     * Ambil grade berikutnya dari nama kelas.
     * Return null jika XII (lulus).
     *
     * Support format "X RPL 1" (spasi) dan "X-RPL-1" (dash).
     */
    protected function getNextGrade(string $className): ?string
    {
        $firstToken = explode('-', $className)[0] ?? '';
        if (!isset(self::GRADE_MAP[$firstToken])) {
            $firstToken = explode(' ', $className)[0] ?? '';
        }

        return self::GRADE_MAP[$firstToken] ?? null;
    }

    /**
     * Ganti grade dalam nama kelas.
     * "X RPL 1" + "XI" → "XI RPL 1"
     * "X-PPLG-1" + "XI" → "XI-PPLG-1"
     */
    protected function replaceGrade(string $className, string $newGrade): string
    {
        if (str_contains($className, '-')) {
            $parts = explode('-', $className);
            $parts[0] = $newGrade;
            return implode('-', $parts);
        }

        $parts = explode(' ', $className);
        $parts[0] = $newGrade;
        return implode(' ', $parts);
    }

    /**
     * Buat saldo awal untuk siswa di tahun ajaran baru (carry forward).
     */
    protected function createOpeningBalance(Student $student, AcademicYear $academicYear): bool
    {
        $exists = PointLedger::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('transaction_type', PointLedger::TYPE_OPENING)
            ->exists();

        if ($exists) {
            return false;
        }

        $lastBalance = PointLedger::getLastBalance($student->id);
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
            'created_by'       => auth()->id(),
            'verified_by'      => auth()->id(),
        ]);

        return true;
    }
}
