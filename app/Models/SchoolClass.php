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
        'homeroom_teacher_id',
        'assistant_teacher_id',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [];
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

    /** Rombel (bagian akhir nama kelas), mis. "VII 1" → "1", "VII A" → "A". */
    public function getRombelAttribute(): ?string
    {
        $parts = preg_split('/[\s-]+/', trim((string) $this->name));

        return $parts ? end($parts) : null;
    }

    /** Nama kelas disimpan lengkap, mis. "VII 1". */
    public function getDisplayNameAttribute(): string
    {
        return (string) $this->name;
    }
}
