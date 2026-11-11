<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;

/**
 * Same authorization pattern as LeadPolicy/ClientPolicy: coarse role check
 * matching the `role:` middleware gating on the route group.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isTeam();
    }

    public function view(User $user, Project $project): bool
    {
        return $user->role->isTeam();
    }

    public function create(User $user): bool
    {
        return $user->role->isTeam();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->role->isTeam();
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin;
    }
}
