<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DisciplineCase extends Model
{
    protected $fillable = [
        'case_number',
        'student_id',
        'violation_category_id',
        'report_by',
        'status',
        'location',
        'description',
        'validated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function violationCategory(): BelongsTo
    {
        return $this->belongsTo(ViolationCategory::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'report_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function pointLedger(): HasOne
    {
        return $this->hasOne(PointLedger::class, 'source_id');
    }
}
