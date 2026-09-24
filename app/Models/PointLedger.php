<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointLedger extends Model
{
    // BUG NOTE: Migration 2026_01_01_000008 defines `verified_at` as
    // foreignId()->constrained('users') — a timestamp was intended.
    // Correct column type should be: timestamp('verified_at')->nullable()
    // Do NOT alter the migration here; handle via cast + application logic.

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

    // Konstanta buat ga typo
    public const TYPE_OPENING = 'OPENING_BALANCE';
    public const TYPE_ACHIEVEMENT = 'ACHIEVEMENT';
    public const TYPE_VIOLATION = 'VIOLATION';
    public const TYPE_RECOVERY = 'RECOVERY';
    public const TYPE_REVERSAL = 'REVERSAL';
    public const DIR_CREDIT = 'credit';
    public const DIR_DEBIT = 'debit';
    public const OPENING_AMOUNT = '2000';

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

    /** Ambil saldo terakhir siswa dari tahun ajaran manapun (terbaru). */
    public static function getLastBalance(int $studentId): int
    {
        $last = static::where('student_id', $studentId)
            ->latest('id')
            ->value('balance_after');

        return $last !== null ? (int) $last : (int) static::OPENING_AMOUNT;
    }

    /** Label human-readable untuk sumber transaksi (ganti 'App\Models\X #1') */
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
}
