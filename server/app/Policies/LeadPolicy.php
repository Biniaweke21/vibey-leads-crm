<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;

/**
 * Coarse role check — matches the `role:` middleware gating on the route
 * group: any team member (Admin or Team Member) can view/create/update,
 * Admin only can delete.
 */
class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isTeam();
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->role->isTeam();
    }

    /** Team members create leads from the admin dashboard; clients create them via the "Start New Project" portal form — same public-intake shape either way. */
    public function create(User $user): bool
    {
        return $user->role->isTeam() || $user->role === UserRole::Client;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->role->isTeam();
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->role === UserRole::Admin;
    }
}
