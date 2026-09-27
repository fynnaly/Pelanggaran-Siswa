<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'nisn',
        'nis',
        'full_name',
        'gender',
        'birth_date',
        'phone',
        'address',
        'parent_name',
        'parent_phone',
        'avatar_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function disciplineCases(): HasMany
    {
        return $this->hasMany(DisciplineCase::class);
    }

    public function achievementRecords(): HasMany
    {
        return $this->hasMany(AchievementRecord::class);
    }

    public function pointLedgers(): HasMany
    {
        return $this->hasMany(PointLedger::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Enrollment aktif terakhir — relationship agar bisa di-eager-load. */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)
            ->where('status', 'active')
            ->latest('id');
    }

    /** Enrollment terakhir (bila tidak ada yang active) — dipakai fallback. */
    public function latestEnrollment(): ?Enrollment
    {
        return $this->enrollments()
            ->with(['schoolClass', 'academicYear'])
            ->latest('id')
            ->first();
    }

    /** Enrollment aktif di tahun ajaran tertentu. */
    public function enrollmentForYear(int $yearId): ?Enrollment
    {
        return $this->enrollments()
            ->where('academic_year_id', $yearId)
            ->with(['schoolClass', 'academicYear'])
            ->first();
    }

    public function getClassNameAttribute(): string
    {
        return $this->latestEnrollment()?->schoolClass?->name ?? '-';
    }

    public function getAcademicYearNameAttribute(): string
    {
        return $this->latestEnrollment()?->academicYear?->name ?? '-';
    }

    /** Saldo poin terakhir (terbaru) siswa — fallback saldo awal 2000. */
    public function getCurrentBalanceAttribute(): int
    {
        $latest = $this->pointLedgers()->latest('id')->value('balance_after');
        return $latest !== null ? (int) $latest : (int) PointLedger::OPENING_AMOUNT;
    }

    /**
     * Zona pembinaan siswa berdasarkan saldo.
     * Return array dari config/zones.php (name, responsible, action, color).
     */
    public function getZoneAttribute(): array
    {
        $balance = $this->current_balance;

        foreach (config('zones.zones', []) as $zone) {
            $minOk = $zone['min'] === null || $balance >= $zone['min'];
            $maxOk = $zone['max'] === null || $balance <= $zone['max'];
            if ($minOk && $maxOk) {
                return $zone;
            }
        }

        return ['name' => 'Normal', 'responsible' => null, 'action' => null, 'color' => 'success'];
    }
}
