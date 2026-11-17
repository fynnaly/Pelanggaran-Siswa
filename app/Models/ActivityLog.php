<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Catat aktivitas admin. Kegagalan menulis log tidak boleh menggagalkan
     * aksi utama (import / generate), jadi semua error ditelan di sini.
     */
    public static function record(
        string $action,
        string $description,
        array $properties = [],
        ?Model $subject = null,
    ): ?self {
        try {
            return self::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'properties' => $properties === [] ? null : $properties,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Label ramah untuk aksi. */
    public static function labelFor(string $action): string
    {
        return match ($action) {
            'user.create' => 'Tambah pengguna',
            'user.update' => 'Edit pengguna',
            'user.deactivate' => 'Nonaktifkan akun',
            'user.generate' => 'Generate akun',
            'user.password_reset' => 'Reset password',
            'user.import' => 'Import pengguna',
            'student.create' => 'Tambah siswa',
            'student.import' => 'Import siswa',
            'teacher.import' => 'Import guru',
            default => ucfirst(str_replace(['.', '_'], [' · ', ' '], $action)),
        };
    }

    /** Nada badge: success / warning / danger / info. */
    public function tone(): string
    {
        return match (true) {
            str_contains($this->action, 'import') => ($this->properties['status'] ?? 'ok') === 'failed'
                ? 'danger'
                : (($this->properties['skipped'] ?? 0) > 0 ? 'warning' : 'success'),
            $this->action === 'user.deactivate' => 'danger',
            in_array($this->action, ['user.generate', 'user.password_reset', 'user.create', 'student.create'], true) => 'success',
            default => 'info',
        };
    }

    public function label(): string
    {
        return self::labelFor($this->action);
    }

    /** Ringkasan singkat jumlah data yang terlibat, bila ada. */
    public function summary(): string
    {
        $p = $this->properties ?? [];
        $parts = [];

        foreach (['imported' => 'ditambahkan', 'skipped' => 'dilewati', 'total' => 'total', 'classes_created' => 'kelas baru'] as $key => $label) {
            if (isset($p[$key]) && is_numeric($p[$key])) {
                $parts[] = (int) $p[$key].' '.$label;
            }
        }

        return implode(' · ', $parts);
    }
}
