<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'nip',
        'full_name',
        'gender',
        'mata_pelajaran',
        'jabatan',
        'phone',
        'avatar_path',
        'status',
    ];

    /** Akun pengguna yang ditautkan ke data guru ini. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Hanya guru berstatus aktif. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Foto profil guru; memakai foto akun bila ada, lalu gambar default. */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path) {
            return asset('storage/'.$this->avatar_path);
        }

        return $this->user?->avatar_url ?? asset('assets/images/user/default-avatar.png');
    }
}
