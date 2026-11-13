<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Enums\ProjectVisibility;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

class ProjectPolicy
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
    public function view(User $user, Project $project): bool
    {
        if ($this->isWorkspaceAdmin($user, $project)) {
            return true;
        }

        return match (ProjectVisibility::from((string) $project->getRawOriginal('visibility'))) {
            ProjectVisibility::Workspace => $project->workspace->memberships()->whereBelongsTo($user)->exists(),
            ProjectVisibility::Unit => $project->unit->memberships()->whereBelongsTo($user)->exists(),
            ProjectVisibility::Private => $project->memberships()->whereBelongsTo($user)->exists(),
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Workspace $workspace): bool
    {
        return $this->workspaceRole($user, $workspace)?->canManageWorkspace() ?? false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        return ! $project->isArchived() && $this->canManage($user, $project);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $project->isArchived() && $this->canManage($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }

    public function archive(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    private function canManage(User $user, Project $project): bool
    {
        return $this->isWorkspaceAdmin($user, $project)
            || $project->memberships()
                ->whereBelongsTo($user)
                ->where('role', ProjectRole::ProjectAdmin->value)
                ->exists();
    }

    private function isWorkspaceAdmin(User $user, Project $project): bool
    {
        return $this->workspaceRole($user, $project->workspace)?->canManageWorkspace() ?? false;
    }

    private function workspaceRole(User $user, Workspace $workspace): ?WorkspaceRole
    {
        $membership = $workspace->memberships()->whereBelongsTo($user)->first();

        return $membership ? WorkspaceRole::from((string) $membership->getRawOriginal('role')) : null;
    }
}
