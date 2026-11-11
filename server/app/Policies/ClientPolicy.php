<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;

/**
 * Same authorization pattern as LeadPolicy: coarse role check matching the
 * `role:` middleware gating on the route group.
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isTeam();
    }

    public function view(User $user, Client $client): bool
    {
        return $user->role->isTeam();
    }

    public function create(User $user): bool
    {
        return $user->role->isTeam();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->role->isTeam();
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->role === UserRole::Admin;
    }
}
