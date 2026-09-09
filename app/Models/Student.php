<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'class_id',
        'nisn',
        'nis',
        'full_name',
        'username',
        'avatar_path',
        'is_leaderboard_visible',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_leaderboard_visible' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Spec: belongsTo(SchoolClass::class, 'class') — 'class' is reserved keyword,
     * so method is schoolClass() with FK class_id (migration column).
     * Access via $student->schoolClass; alias handled via getAttribute if needed.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function disciplineCases(): HasMany
    {
        return $this->hasMany(DisciplineCase::class);
    }

    public function achievementRecords(): HasMany
    {
        return $this->hasMany(AchievementRecord::class);
    }

    public function pointLedgers(): HasMany
    {
        return $this->hasMany(PointLedger::class);
    }
}
