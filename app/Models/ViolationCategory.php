<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'severity',
        'points',
        'group_type',
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
