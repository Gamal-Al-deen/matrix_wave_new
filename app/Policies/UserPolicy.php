<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only administrators may browse the customer directory.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    /**
     * Account data is visible to its owner and to administrators only.
     */
    public function view(User $actor, User $user): bool
    {
        return $actor->isAdmin() || $actor->id === $user->id;
    }

    /**
     * Customers may update their own profile; administrators may update any.
     */
    public function update(User $actor, User $user): bool
    {
        return $actor->isAdmin() || $actor->id === $user->id;
    }
}
