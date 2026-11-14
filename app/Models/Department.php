<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
    ];

    /** Hanya jurusan berstatus aktif */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** Kelas-kelas yang terhubung ke jurusan ini */
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'department_id');
    }

    /** Apakah jurusan berstatus aktif */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
