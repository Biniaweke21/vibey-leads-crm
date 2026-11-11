<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Meeting;
use App\Models\User;

/**
 * Same authorization pattern as Lead/Client/Project/DocumentPolicy: coarse
 * role check matching the `role:` middleware gating on the route group.
 */
class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isTeam();
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $user->role->isTeam();
    }

    public function create(User $user): bool
    {
        return $user->role->isTeam();
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->role->isTeam();
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->role === UserRole::Admin;
    }
}
