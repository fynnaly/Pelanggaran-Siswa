<?php
namespace App\Policies;

use App\Models\User;
use App\Models\DisciplineCase;

class DisciplineCasePolicy
{
    /** Guru dan admin bisa buat laporan pelanggaran. */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['guru', 'admin']);
    }

    /**
     * Update status kasus (found → clarifying → classifying → handling → monitoring → closed).
     * Hanya BK, Kesiswaan, dan admin yang boleh memajukan status.
     */
    public function update(User $user, DisciplineCase $case): bool
    {
        return in_array($case->status, ['found', 'clarifying', 'classifying', 'handling', 'monitoring'], true)
            && $user->hasAnyRole(['bk', 'kesiswaan', 'admin']);
    }

    /** Buang kasus (dismissed): hanya BK, Kesiswaan, dan admin. */
    public function dismiss(User $user, DisciplineCase $case): bool
    {
        return in_array($case->status, ['found', 'clarifying', 'classifying'], true)
            && $user->hasAnyRole(['bk', 'kesiswaan', 'admin']);
    }
}
