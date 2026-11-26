<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceMembership;

class WorkspaceMembershipPolicy
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
    public function view(User $user, WorkspaceMembership $workspaceMembership): bool
    {
        return $user->isAdmin() || $workspaceMembership->workspace->memberships()->whereBelongsTo($user)->exists();
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
    public function update(User $user, WorkspaceMembership $workspaceMembership): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $actorRole = $this->actorRole($user, $workspaceMembership);

        if ($actorRole === WorkspaceRole::Owner) {
            return true;
        }

        return $actorRole?->canManageMembers() === true
            && WorkspaceRole::from((string) $workspaceMembership->getRawOriginal('role')) !== WorkspaceRole::Owner;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkspaceMembership $workspaceMembership): bool
    {
        return $this->update($user, $workspaceMembership);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkspaceMembership $workspaceMembership): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkspaceMembership $workspaceMembership): bool
    {
        return false;
    }

    private function actorRole(User $user, WorkspaceMembership $workspaceMembership): ?WorkspaceRole
    {
        $membership = $workspaceMembership->workspace->memberships()->whereBelongsTo($user)->first();

        return $membership ? WorkspaceRole::from((string) $membership->getRawOriginal('role')) : null;
    }
}
