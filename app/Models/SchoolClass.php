<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'academic_year_id',
        'name',
        'grade_level',
        'angkatan',
        'homeroom_teacher_id',
        'assistant_teacher_id',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [];
    }

    /**
     * Isi angkatan otomatis saat kelas dibuat / tingkatan atau tahun ajarannya berubah.
     * Nilai eksplisit pada kelas baru tetap dihormati.
     */
    protected static function booted(): void
    {
        static::saving(function (self $class) {
            if (! $class->exists && $class->angkatan !== null) {
                return;
            }

            if ($class->angkatan === null || $class->isDirty(['academic_year_id', 'grade_level'])) {
                $class->angkatan = self::resolveAngkatan($class->academicYear()->first(), $class->grade_level);
            }
        });
    }

    /**
     * Angkatan = tahun masuk siswa berdasarkan tingkatan.
     * VII = tahun awal tahun ajaran, VIII = tahun awal - 1, IX = tahun awal - 2.
     */
    public static function resolveAngkatan(?AcademicYear $year, ?string $gradeLevel): ?int
    {
        $startYear = $year?->startYear();

        if ($startYear === null) {
            return null;
        }

        $offset = match (strtoupper((string) $gradeLevel)) {
            'VIII' => 1,
            'IX' => 2,
            default => 0,
        };

        return $startYear - $offset;
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    /** Guru pembantu (Guru 2) penanggung jawab kelas. */
    public function assistantTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_teacher_id');
    }

    /** Siswa terdaftar di kelas ini lewat enrollments (fase 2). */
    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(Student::class, Enrollment::class, 'class_id', 'id', 'id', 'student_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    /** Tingkat format resmi: "VII/7", "VIII/8", "IX/9". */
    public function getTingkatAttribute(): ?string
    {
        return match ($this->grade_level) {
            'VII' => 'VII/7',
            'VIII' => 'VIII/8',
            'IX' => 'IX/9',
            default => null,
        };
    }

    /** Rombel (bagian akhir nama kelas), mis. "VII A" → "A". */
    public function getRombelAttribute(): ?string
    {
        $parts = preg_split('/[\s-]+/', trim((string) $this->name));

        return $parts ? end($parts) : null;
    }

    /** Nama kelas disimpan lengkap, mis. "VII A". */
    public function getDisplayNameAttribute(): string
    {
        return (string) $this->name;
    }
}
