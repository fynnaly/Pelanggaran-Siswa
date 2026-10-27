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
     * Hanya BK, Kesiswaan, dan admin yang boleh memajukan status sebelum closed/dismissed.
     */
    public function update(User $user, DisciplineCase $case): bool
    {
        return in_array($case->status, ['found', 'handling', 'clarifying', 'classifying', 'monitoring'], true)
            && $user->hasAnyRole(['bk', 'kesiswaan', 'admin']);
    }

    /** Buang kasus (dismissed): hanya sebelum masuk penanganan/potong poin. */
    public function dismiss(User $user, DisciplineCase $case): bool
    {
        return in_array($case->status, ['found', 'clarifying', 'classifying'], true)
            && $user->hasAnyRole(['bk', 'kesiswaan', 'admin']);
    }
}
