<?php

namespace App\Policies;

use App\Models\User;

class AboutPageContentPolicy
{
    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }
}
