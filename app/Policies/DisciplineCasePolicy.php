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

    public function validate(User $user, DisciplineCase $case) {
        return $case->status === 'found' && $user->hasRole('bk');
    }

    public function done(User $user, DisciplineCase $case): bool {
        return $case->status === 'validated' && $user->hasRole('bk');
    }
}
