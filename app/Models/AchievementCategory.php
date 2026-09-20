<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AchievementCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'points',
        'status',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function achievementRecords(): HasMany
    {
        return $this->hasMany(AchievementRecord::class);
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
