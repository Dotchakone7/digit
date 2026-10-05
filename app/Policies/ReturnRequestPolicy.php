<?php

namespace App\Policies;

use App\Models\ReturnRequest;
use App\Models\User;

class ReturnRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('returns.manage');
    }

    public function update(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->can('returns.manage');
    }
}
