<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'severity',
        'points',
        'status',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function disciplineCases(): HasMany
    {
        return $this->hasMany(DisciplineCase::class);
    }
}
