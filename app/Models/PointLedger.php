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
}
