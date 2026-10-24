<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DisciplineCase extends Model
{
    protected $fillable = [
        'case_number',
        'student_id',
        'enrollment_id',
        'class_id',
        'violation_category_id',
        'academic_year_id',
        'reporter_id',
        'status',
        'severity_override',
        'points_override',
        'location',
        'incident_date',
        'incident_time',
        'description',
        'clarification_notes',
        'classification_notes',
        'validation_notes',
        'handling_notes',
        'monitoring_notes',
        'assigned_to',
        'validator_id',
        'validated_at',
        'closed_at',
        'closed_by',
        'is_recurring',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'incident_time' => 'datetime:H:i',
            'validated_at' => 'datetime',
            'closed_at' => 'datetime',
            'is_recurring' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function violationCategory(): BelongsTo
    {
        return $this->belongsTo(ViolationCategory::class);
    }

    /** Enrollment siswa saat kejadian (snapshot — tidak berubah walau siswa pindah). */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** Kelas saat kejadian (snapshot — source of truth historis per kasus). */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function pointLedger(): HasOne
    {
        return $this->hasOne(PointLedger::class, 'source_id')->where('source_type', self::class);
    }

    /**
     * Generate nomor kasus unik format KAS-YYYY-0001.
     */
    public static function generateCaseNumber(): string
    {
        $prefix = 'KAS-' . date('Y') . '-';
        $last = self::where('case_number', 'like', $prefix . '%')->max('case_number');
        if ($last) {
            $number = intval(substr($last, -4)) + 1;
        } else {
            $number = 1;
        }
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Nomor kejadian kasus ini untuk kategori + tahun ajaran yang sama (1 = pertama).
     * Hanya kasus non-dismissed yang dihitung — kasus ditolak tidak memicu eskalasi.
     */
    public function getOccurrenceNumber(): int
    {
        return self::where('student_id', $this->student_id)
            ->where('violation_category_id', $this->violation_category_id)
            ->where('academic_year_id', $this->academic_year_id)
            ->where('id', '<=', $this->id)
            ->where('status', '!=', 'dismissed')
            ->count();
    }

    /**
     * Poin efektif dengan eskalasi pelanggaran berulang (3 batas toleransi).
     *
     * Sumber kebenaran rumus ada di ViolationCategory::tierPoints() supaya UI dan
     * perhitungan ledger memakai tabel batas yang sama:
     *   ke-1 = points, ke-2 = points + increment, ke-3 = max_recurring_points.
     *   Pelanggaran ke-4 dst. tetap memakai poin batas akhir.
     *
     * Contoh P1.1 (5/10/15): ke-1 = 5, ke-2 = 10, ke-3 = 15, ke-4 = 15.
     * Override manual (points_override) selalu menang — eskalasi tidak dipakai.
     */
    public function getEffectivePoints(): int
    {
        if ($this->points_override !== null) {
            return (int) $this->points_override;
        }

        $category = $this->violationCategory;

        if (! $category) {
            return 0;
        }

        return $category->pointsForOccurrence($this->getOccurrenceNumber());
    }

    /** Batas toleransi yang sedang berlaku untuk kasus ini (1..3). */
    public function getCurrentTier(): int
    {
        return min($this->getOccurrenceNumber(), ViolationCategory::MAX_TIER);
    }

    /** Tingkat efektif (override atau dari kategori). */
    public function getEffectiveSeverity(): string
    {
        return $this->severity_override ?? $this->violationCategory->severity;
    }
}
