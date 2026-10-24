<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AchievementCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'points',
        'group_type',
        'parent_id',
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

    /** Kategori induk (null = kategori induk R1-R10). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Daftar sub di bawah kategori ini. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Kategori induk = baris tanpa parent_id. */
    public function isParent(): bool
    {
        return $this->parent_id === null;
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
