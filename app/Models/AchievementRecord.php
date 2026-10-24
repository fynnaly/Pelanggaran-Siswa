<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class AchievementRecord extends Model
{
    protected $fillable = [
        'student_id',
        'enrollment_id',
        'class_id',
        'achievement_category_id',
        'discipline_case_id',
        'academic_year_id',
        'recorded_by',
        'status',
        'event_name',
        'event_date',
        'event_period',
        'achievement_level',
        'points_awarded',
        'description',
        'evidence_path',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'verified_at' => 'datetime',
            'event_period' => 'string',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function achievementCategory(): BelongsTo
    {
        return $this->belongsTo(AchievementCategory::class);
    }

    /**
     * Kasus pelanggaran yang ditebus prestasi ini (nullable).
     * Prestasi berpasangan dengan kasus = sanksi sudah dijalani + poin dipulihkan.
     */
    public function disciplineCase(): BelongsTo
    {
        return $this->belongsTo(DisciplineCase::class, 'discipline_case_id');
    }

    public function isRedemption(): bool
    {
        return $this->discipline_case_id !== null;
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function pointLedger(): HasOne
    {
        return $this->hasOne(PointLedger::class, 'source_id');
    }

    /** Siswa yang meraih prestasi. */
    public function getEffectivePoints(): int
    {
        return $this->points_awarded;
    }
}