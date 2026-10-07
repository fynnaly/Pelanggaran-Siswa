<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'sanksi',
        'severity',
        'points',
        'group_type',
        'parent_id',
        'is_zero_tolerance',
        'is_recurring',
        'recurring_increment',
        'max_recurring_points',
        'status',
        'effective_date',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'is_zero_tolerance' => 'boolean',
            'is_recurring' => 'boolean',
            'effective_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function disciplineCases(): HasMany
    {
        return $this->hasMany(DisciplineCase::class);
    }

    /** Kategori induk (null = kategori induk). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Daftar sub-kategori di bawah kategori ini. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Rekomendasi pemulihan otomatis (sub-prestasi padanan) untuk pelanggaran ini. */
    public function redemptionRules(): HasMany
    {
        return $this->hasMany(RedemptionRule::class);
    }

    /** Kategori induk = baris tanpa parent_id. */
    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    /** Sub-kategori aktif = kandidat pemilihan pada form kasus. */
    public function isLeafActive(): bool
    {
        return $this->parent_id !== null && $this->isActive();
    }

    /**
     * Cek apakah kategori aktif (berdasarkan status + rentang tanggal).
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && (!$this->effective_date || $this->effective_date->isPast())
            && (!$this->expiry_date || $this->expiry_date->isFuture());
    }
}
