<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    /**
     * Pastikan data guru memiliki akun pengguna yang tertaut.
     * Bila belum ada, buatkan akun baru secara otomatis dengan role guru & wali_kelas.
     */
    public function ensureUserAccount(): User
    {
        if ($this->user_id && $user = User::find($this->user_id)) {
            if (! $user->hasRole('wali_kelas')) {
                $user->assignRole('wali_kelas');
            }

            return $user;
        }

        $baseName = Str::before($this->full_name, ',');
        $slug = Str::slug($baseName, '');
        if (! $slug) {
            $slug = 'guru'.$this->id;
        }
        $domain = 'smpbn666.sch.id';
        $email = $slug.'@'.$domain;
        $count = 1;
        while (User::where('email', $email)->exists()) {
            $count++;
            $email = $slug.'.'.$count.'@'.$domain;
        }

        $user = User::create([
            'name' => $this->full_name,
            'email' => $email,
            'password' => Hash::make('password'),
            'nip' => $this->nip,
        ]);

        $user->syncRoles(['guru', 'wali_kelas']);
        $this->update(['user_id' => $user->id]);

        return $user;
    }
}
