<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return Project::query()->whereKey($task->project_id)->visibleTo($user)->exists();
    }

    public function create(User $user, Project $project): bool
    {
        return ! $project->isArchived() && $this->canWorkInProject($user, $project);
    }

    public function update(User $user, Task $task): bool
    {
        return ! $task->project->isArchived()
            && ($this->isProjectAdmin($user, $task->project)
                || ($this->isContributor($user, $task->project)
                    && ($task->assignee_id === $user->getKey() || $task->created_by_id === $user->getKey())));
    }

    public function transition(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    public function assign(User $user, Task $task): bool
    {
        return ! $task->project->isArchived() && $this->isProjectAdmin($user, $task->project);
    }

    public function comment(User $user, Task $task): bool
    {
        return ! $task->project->isArchived() && $this->canCommentInProject($user, $task->project);
    }

    public function attach(User $user, Task $task): bool
    {
        return $this->comment($user, $task);
    }

    private function canWorkInProject(User $user, Project $project): bool
    {
        return $this->isProjectAdmin($user, $project) || $this->isContributor($user, $project);
    }

    private function canCommentInProject(User $user, Project $project): bool
    {
        return $this->workspaceIsAdmin($user, $project)
            || $project->memberships()->where('user_id', $user->getKey())->whereIn('role', [
                ProjectRole::ProjectAdmin->value,
                ProjectRole::Contributor->value,
                ProjectRole::Commenter->value,
            ])->exists();
    }

    private function isProjectAdmin(User $user, Project $project): bool
    {
        return $this->workspaceIsAdmin($user, $project)
            || $project->memberships()->where('user_id', $user->getKey())->where('role', ProjectRole::ProjectAdmin->value)->exists();
    }

    private function isContributor(User $user, Project $project): bool
    {
        return $project->memberships()->where('user_id', $user->getKey())->whereIn('role', [
            ProjectRole::ProjectAdmin->value,
            ProjectRole::Contributor->value,
        ])->exists();
    }

    private function workspaceIsAdmin(User $user, Project $project): bool
    {
        return $project->workspace->memberships()
            ->where('user_id', $user->getKey())
            ->whereIn('role', [WorkspaceRole::Owner->value, WorkspaceRole::WorkspaceAdmin->value])
            ->exists();
    }
}
