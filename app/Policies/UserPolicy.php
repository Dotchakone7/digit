<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('customers.view');
    }

    /** Admins cannot touch super admins, nobody can lock themselves out. */
    public function update(User $user, User $model): bool
    {
        if (! $user->can('users.manage') || $user->is($model)) {
            return false;
        }

        return ! $model->isSuperAdmin() || $user->isSuperAdmin();
    }

    /** Accounts with orders are deactivated, never deleted (accounting history). */
    public function delete(User $user, User $model): bool
    {
        return $this->update($user, $model) && ! $model->orders()->exists();
    }
}
