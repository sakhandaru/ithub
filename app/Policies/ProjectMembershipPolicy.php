<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\ProjectMembership;
use App\Models\User;

class ProjectMembershipPolicy
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
    public function view(User $user, ProjectMembership $projectMembership): bool
    {
        return $projectMembership->project->memberships()->whereBelongsTo($user)->exists();
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
    public function update(User $user, ProjectMembership $projectMembership): bool
    {
        return $this->canManage($user, $projectMembership);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProjectMembership $projectMembership): bool
    {
        return $this->canManage($user, $projectMembership);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProjectMembership $projectMembership): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProjectMembership $projectMembership): bool
    {
        return false;
    }

    private function canManage(User $user, ProjectMembership $membership): bool
    {
        $project = $membership->project;

        return $project->memberships()
            ->whereBelongsTo($user)
            ->where('role', ProjectRole::ProjectAdmin->value)
            ->exists()
            || $project->workspace->memberships()
                ->whereBelongsTo($user)
                ->whereIn('role', ['owner', 'workspace_admin'])
                ->exists();
    }
}
