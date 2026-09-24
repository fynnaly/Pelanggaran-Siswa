<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DisciplineCase;

class DisciplineCasePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the user can create a discipline case report.
     * Guru (guru role) and admin can report a violation.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['guru', 'admin']);
    }

    /**
     * Determine whether the user can validate a discipline case.
     * Only BK (bk role) or admin can validate a reported case.
     * The case must be in 'found' status (reported, pending validation).
     */
    public function validate(User $user, DisciplineCase $case): bool
    {
        return $case->status === 'found' && $user->hasAnyRole(['bk', 'admin']);
    }

    /**
     * Determine whether the user can mark a discipline case as done/completed.
     * Only BK (bk role) or admin can close a validated case.
     * The case must be in 'validated' status (validated, pending completion).
     */
    public function done(User $user, DisciplineCase $case): bool
    {
        return $case->status === 'validated' && $user->hasAnyRole(['bk', 'admin']);
    }

    /**
     * Determine whether the user can mark a discipline case as dismissed.
     * Only BK (bk role) or admin can dismiss a found case.
     * The case must be in 'found' status (reported, pending dismissal).
     */
    public function dismiss(User $user, DisciplineCase $case): bool
    {
        return $case->status === 'found' && $user->hasAnyRole(['bk', 'admin']);
    }
}
