<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointLedger extends Model
{
    protected $fillabe = [
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
        'verified_by'
    ];

    // Konstanta buat ga typo
    public const TYPE_OPENING = 'OPENING_BALANCE';
    public const TYPE_ACHIEVEMENT = 'ACHIEVEMENT';
    public const TYPE_VIOLATION = 'VIOLATION';
    public const TYPE_RECOVERY = 'RECOVERY';
    public const TYPE_REVERSAL = 'REVERSAL';
    public const DIR_CREDIT = 'credit';
    public const DIR_DEBIT = 'debit';
    public const OPENING_AMOUNT = '2000';

    public function student(): BelongsTo {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
}
