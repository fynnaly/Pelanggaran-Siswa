<?php

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\PointLedger;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionService
{
    /** Grade increment mapping: X→XI, XI→XII */
    protected const GRADE_MAP = [
        'X'  => 'XI',
        'XI' => 'XII',
    ];

    /**
     * Jalankan kenaikan kelas ke tahun ajaran baru.
     *
     * Prinsip:
     * - Kelas lama TIDAK DIUBAH (histori tetap).
     * - Buat kelas baru di tahun ajaran baru.
     * - Siswa yang naik kelas: buat enrollment baru di kelas baru.
     * - XII: graduation → status graduated (enrollment lama tetap).
     * - PointLedger dibuat per tahun ajaran baru.
     *
     * @return array{classes_created: int, students_graduated: int, students_promoted: int, balances_created: int}
     */
    public function promote(AcademicYear $newYear): array
    {
        return DB::transaction(function () use ($newYear) {
            // 1. Cari tahun ajaran sebelumnya
            $previousYear = AcademicYear::where('id', '!=', $newYear->id)
                ->orderByDesc('start_date')
                ->first();

            if (! $previousYear) {
                return ['classes_created' => 0, 'students_graduated' => 0, 'students_promoted' => 0, 'balances_created' => 0];
            }

            // 2. Kunci tahun ajaran lama — ledger historis tidak boleh diubah lagi
            $previousYear->update([
                'status'    => AcademicYear::STATUS_CLOSED,
                'is_locked' => true,
            ]);

            // 3. Ambil semua kelas lama di tahun sebelumnya (TIDAK dimutasi)
            $oldClasses = SchoolClass::where('academic_year_id', $previousYear->id)->get();

            $classesCreated = 0;
            $studentsGraduated = 0;
            $studentsPromoted = 0;
            $balancesCreated = 0;

            foreach ($oldClasses as $oldClass) {
                $newGrade = $this->getNextGrade($oldClass->name);

                // XII → lulus
                if ($newGrade === null) {
                    $graduated = $this->markGraduated($oldClass, $previousYear);
                    $studentsGraduated += $graduated;
                    Log::info("Promotion: {$oldClass->name} ({$previousYear->name}) → {$graduated} graduated");
                    continue;
                }

                // Buat kelas baru di tahun ajaran baru — JANGAN mutasi kelas lama
                $newClassName = $this->replaceGrade($oldClass->name, $newGrade);
                $newClass = SchoolClass::create([
                    'academic_year_id' => $newYear->id,
                    'name'             => $newClassName,
                    'grade_level'      => $oldClass->grade_level,
                    'major'            => $oldClass->major,
                    'homeroom_teacher_id' => $oldClass->homeroom_teacher_id,
                    'capacity'         => $oldClass->capacity,
                ]);
                $classesCreated++;

                Log::info("Promotion: created {$newClassName} ({$newYear->name}) from {$oldClass->name} ({$previousYear->name})");

                // Pindahkan siswa aktif ke kelas baru via enrollment
                $promoted = $this->promoteStudents($oldClass, $newClass, $previousYear);
                $studentsPromoted += $promoted;
            }

            // 3. Generate saldo awal untuk siswa aktif di tahun ajaran baru
            $activeStudents = Student::where('status', 'active')->get();

            foreach ($activeStudents as $student) {
                if ($this->createOpeningBalance($student, $newYear, $previousYear)) {
                    $balancesCreated++;
                }
            }

            // Bersihkan OPENING_BALANCE lama dari tahun sebelumnya
            PointLedger::where('transaction_type', PointLedger::TYPE_OPENING)
                ->where('academic_year_id', $previousYear->id)
                ->whereIn('student_id', $activeStudents->pluck('id'))
                ->delete();

            Log::info("Promotion complete: {$classesCreated} classes created, {$studentsGraduated} graduated, {$studentsPromoted} promoted, {$balancesCreated} balances");

            return [
                'classes_created'    => $classesCreated,
                'students_graduated' => $studentsGraduated,
                'students_promoted'  => $studentsPromoted,
                'balances_created'   => $balancesCreated,
            ];
        });
    }

    /** Tandai siswa aktif di kelas lama sebagai graduated (enrollment + status siswa). */
    protected function markGraduated(SchoolClass $oldClass, AcademicYear $prevYear): int
    {
        $enrollmentIds = Enrollment::where('class_id', $oldClass->id)
            ->where('academic_year_id', $prevYear->id)
            ->where('status', 'active')
            ->pluck('id');

        if ($enrollmentIds->isEmpty()) {
            return 0;
        }

        $studentIds = Enrollment::whereIn('id', $enrollmentIds)->pluck('student_id');

        Enrollment::whereIn('id', $enrollmentIds)->update(['status' => 'graduated', 'ended_at' => now()]);
        Student::whereIn('id', $studentIds)->update(['status' => 'graduated']);

        return $enrollmentIds->count();
    }

    /** Buat enrollment baru untuk siswa aktif di kelas baru. */
    protected function promoteStudents(SchoolClass $oldClass, SchoolClass $newClass, AcademicYear $prevYear): int
    {
        $enrollments = Enrollment::where('class_id', $oldClass->id)
            ->where('academic_year_id', $prevYear->id)
            ->where('status', 'active')
            ->with('student')
            ->get();

        foreach ($enrollments as $enroll) {
            // Cek apakah sudah punya enrollment di tahun ajaran baru (untuk kasus repeating)
            $exists = Enrollment::where('student_id', $enroll->student_id)
                ->where('academic_year_id', $newClass->academic_year_id)
                ->exists();

            if ($exists) {
                // Repeating — ubah status enrollment lama jadi completed, buat baru di kelas yang sama
                $enroll->update(['status' => 'completed', 'ended_at' => now()]);
                $existing = Enrollment::where('student_id', $enroll->student_id)
                    ->where('academic_year_id', $newClass->academic_year_id)
                    ->where('status', 'active')
                    ->first();
                if ($existing && $existing->class_id != $newClass->id) {
                    $existing->update(['class_id' => $newClass->id]);
                }
            } else {
                // Naik kelas — buat enrollment baru di kelas baru
                Enrollment::create([
                    'student_id'     => $enroll->student_id,
                    'class_id'       => $newClass->id,
                    'academic_year_id' => $newClass->academic_year_id,
                    'status'         => 'active',
                    'enrolled_at'    => now(),
                ]);
            }
        }

        return $enrollments->count();
    }

    /** Ambil grade berikutnya dari nama kelas. Return null jika XII (lulus). */
    protected function getNextGrade(string $className): ?string
    {
        $firstToken = explode('-', $className)[0] ?? '';
        if (! isset(self::GRADE_MAP[$firstToken])) {
            $firstToken = explode(' ', $className)[0] ?? '';
        }

        return self::GRADE_MAP[$firstToken] ?? null;
    }

    /** Ganti grade dalam nama kelas. "X RPL 1" + "XI" → "XI RPL 1" */
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

    /** Buat saldo awal untuk siswa di tahun ajaran baru (carry forward dari tahun sebelumnya). */
    protected function createOpeningBalance(Student $student, AcademicYear $academicYear, AcademicYear $previousYear): bool
    {
        if (PointLedger::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('transaction_type', PointLedger::TYPE_OPENING)
            ->exists()) {
            return false;
        }

        // Saldo dibawa dari tahun sebelumnya (bukan dari tahun baru yang belum ada transaksi)
        $carryForward = PointLedger::where('student_id', $student->id)
            ->where('academic_year_id', $previousYear->id)
            ->latest('id')
            ->value('balance_after') ?? PointLedger::OPENING_AMOUNT;

        PointLedger::create([
            'student_id'       => $student->id,
            'academic_year_id' => $academicYear->id,
            'direction'        => PointLedger::DIR_CREDIT,
            'amount'           => PointLedger::OPENING_AMOUNT,
            'balance_after'    => $carryForward,
            'transaction_type' => PointLedger::TYPE_OPENING,
            'reason'           => "Saldo awal tahun ajaran {$academicYear->name}",
            'created_by'       => auth()->id(),
            'verified_by'      => auth()->id(),
        ]);

        return true;
    }
}
