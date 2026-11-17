<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
        'is_locked',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    /**
     * Tahun awal periode, mis. "2026/2027" → 2026.
     * Fallback ke tahun dari start_date bila nama tidak memuat tahun.
     */
    public function startYear(): ?int
    {
        if (preg_match('/(\d{4})/', (string) $this->name, $matches)) {
            return (int) $matches[1];
        }

        return $this->start_date?->year;
    }

    /**
     * Tanggal berakhir efektif: end_date jika diisi, jika tidak start_date + 1 tahun
     * (form tidak menginput end_date, jadi periode standar dipakai sebagai batas).
     */
    public function effectiveEndDate(): ?Carbon
    {
        return $this->end_date ?? $this->start_date?->copy()->addYear();
    }

    /** Sudah berlalu = tanggal berakhir efektifnya sudah lewat dari hari ini. */
    public function isExpired(): bool
    {
        $end = $this->effectiveEndDate();

        return $end !== null && $end->isPast();
    }

    public function pointLedgers(): HasMany
    {
        return $this->hasMany(PointLedger::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
