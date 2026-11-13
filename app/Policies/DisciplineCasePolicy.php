<?php
namespace App\Policies;

use App\Models\User;
use App\Models\DisciplineCase;

class DisciplineCasePolicy
{
    /** Guru, BK, Kesiswaan, dan admin bisa buat laporan pelanggaran. */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['guru', 'bk', 'kesiswaan', 'admin']);
    }

    /**
     * Update status kasus (alur ringkas: found → handling → closed).
     * Validasi status kasus hanya oleh admin dan kepala_sekolah.
     * BK dan Kesiswaan fokus pada pelaporan & pembinaan, tanpa sistem validasi status kasus.
     */
    public function update(User $user, DisciplineCase $case): bool
    {
        return in_array($case->status, ['found', 'handling', 'clarifying', 'classifying', 'monitoring'], true)
            && $user->hasAnyRole(['admin', 'kepala_sekolah']);
    }

    /** Buang kasus (dismissed): hanya oleh admin dan kepala_sekolah sebelum masuk penanganan/potong poin. */
    public function dismiss(User $user, DisciplineCase $case): bool
    {
        return in_array($case->status, ['found', 'clarifying', 'classifying'], true)
            && $user->hasAnyRole(['admin', 'kepala_sekolah']);
    }
}
