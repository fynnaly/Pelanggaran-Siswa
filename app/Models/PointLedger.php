<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointLedger extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'direction',
        'amount',
        'balance_after',
        'transaction_type',
        'source_type',
        'source_id',
        'reason',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public const TYPE_OPENING = 'OPENING_BALANCE';
    public const TYPE_ACHIEVEMENT = 'ACHIEVEMENT';
    public const TYPE_VIOLATION = 'VIOLATION';
    public const TYPE_RECOVERY = 'RECOVERY';
    public const TYPE_REVERSAL = 'REVERSAL';
    public const DIR_CREDIT = 'credit';
    public const DIR_DEBIT = 'debit';
    public const OPENING_AMOUNT = 2000;

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function source(): BelongsTo
    {
        return $this->morphTo();
    }

    /** Label human-readable untuk sumber transaksi. */
    public function sourceLabel(): string
    {
        if ($this->source_type === null || $this->source_id === null) {
            return '-';
        }
        if ($this->source_type === DisciplineCase::class) {
            $case = DisciplineCase::find($this->source_id);
            return $case?->case_number ?? 'Kasus #' . $this->source_id;
        }
        return class_basename($this->source_type) . ' #' . $this->source_id;
    }

    /** Label direction. */
    public function directionLabel(): string
    {
        return $this->direction === 'credit' ? 'Kredit (+)' : 'Debit (-)';
    }

    /** Ambil saldo terakhir siswa — jika tahun ajaran diisi, scope per tahun; jika null, global (carry forward). */
    public static function getLastBalance(int $studentId, ?int $academicYearId = null): int
    {
        $q = self::where('student_id', $studentId);

        if ($academicYearId !== null) {
            $q->where('academic_year_id', $academicYearId);
        }

        return $q->latest('id')->value('balance_after') ?? self::OPENING_AMOUNT;
    }

    /** Cek apakah siswa sudah punya OPENING_BALANCE di tahun ini. */
    public static function hasOpeningBalance(int $studentId, int $academicYearId): bool
    {
        return self::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('transaction_type', self::TYPE_OPENING)
            ->exists();
    }

    /** Buat OPENING_BALANCE +2000 untuk siswa. */
    public static function createOpeningBalance(int $studentId, int $academicYearId, int $createdBy): self
    {
        if (self::hasOpeningBalance($studentId, $academicYearId)) {
            return self::where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->where('transaction_type', self::TYPE_OPENING)
                ->first();
        }
        return self::create([
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'direction' => self::DIR_CREDIT,
            'amount' => self::OPENING_AMOUNT,
            'balance_after' => self::OPENING_AMOUNT,
            'transaction_type' => self::TYPE_OPENING,
            'reason' => 'Saldo pembuka tahun ajaran',
            'created_by' => $createdBy,
        ]);
    }
}
