<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Document;
use App\Models\User;

/**
 * Same authorization pattern as Lead/Client/ProjectPolicy: coarse role
 * check matching the `role:` middleware gating on the route group.
 */
class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isTeam();
    }

    public function view(User $user, Document $document): bool
    {
        return $user->role->isTeam();
    }

    public function create(User $user): bool
    {
        return $user->role->isTeam();
    }

    public function update(User $user, Document $document): bool
    {
        return $user->role->isTeam();
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->role === UserRole::Admin;
    }
}
