<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AchievementRecord extends Model
{
    protected $fillable = [
        'student_id',
        'achievement_category_id',
        'recorded_by',
        'status',
        'description',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function achievementCategory(): BelongsTo
    {
        return $this->belongsTo(AchievementCategory::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function pointLedger(): HasOne
    {
        return $this->hasOne(PointLedger::class, 'source_id');
    }
}
