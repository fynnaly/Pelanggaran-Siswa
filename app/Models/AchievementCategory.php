<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AchievementCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'points',
        'group_type',
        'max_points_per_event',
        'status',
        'effective_date',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function achievementRecords(): HasMany
    {
        return $this->hasMany(AchievementRecord::class);
    }

    public function redemptionRules(): HasMany
    {
        return $this->hasMany(RedemptionRule::class);
    }

    /**
     * Kategori prestasi selalu aktif saat dibuat.
     * Tidak perlu input status di form.
     */
    protected static function booted(): void
    {
        static::creating(fn ($model) => $model->status ??= 'active');
    }
}
