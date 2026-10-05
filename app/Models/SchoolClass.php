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
        'major',
        'homeroom_teacher_id',
        'assistant_teacher_id',
        'capacity',
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

    /** Tingkat format resmi Kurikulum Merdeka: "X/10", "XI/11", "XII/12". */
    public function getTingkatAttribute(): ?string
    {
        return match ($this->grade_level) {
            'X' => 'X/10',
            'XI' => 'XI/11',
            'XII' => 'XII/12',
            default => null,
        };
    }

    /** Nama kelas format: "X RPL 1" */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->grade_level} {$this->major} " . substr($this->name, -1);
    }
}
