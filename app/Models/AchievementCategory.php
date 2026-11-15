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

    /**
     * Kategori penebusan universal — berlaku untuk kasus apa pun yang tidak
     * memiliki pasangan kategori khusus: "Tidak pernah melanggar tata tertib
     * minimal 3/6/9/12 bulan berturut-turut" (sub dari Kedisiplinan).
     *
     * @return array<int, array{id: int, code: string, name: string, poin: int}>
     */
    public static function universalRecoveryOptions(): array
    {
        return self::query()
            ->whereNotNull('parent_id')
            ->where('status', 'active')
            ->where('name', 'like', 'Tidak pernah melanggar tata tertib minimal%')
            ->where(function ($query) {
                $query->whereNull('effective_date')->orWhereDate('effective_date', '<=', today());
            })
            ->where(function ($query) {
                $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today());
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'points'])
            ->map(fn (self $category) => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'poin' => (int) $category->points,
            ])
            ->all();
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
