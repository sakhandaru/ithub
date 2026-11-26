<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\UnitMembership;
use App\Models\User;

class UnitMembershipPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UnitMembership $unitMembership): bool
    {
        return $user->isAdmin() || $unitMembership->unit->memberships()->whereBelongsTo($user)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UnitMembership $unitMembership): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UnitMembership $unitMembership): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $membership = $unitMembership->unit->workspace->memberships()->whereBelongsTo($user)->first();

        return $membership ? WorkspaceRole::from((string) $membership->getRawOriginal('role'))->canManageMembers() : false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UnitMembership $unitMembership): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UnitMembership $unitMembership): bool
    {
        return false;
    }
}
