<?php

namespace App\Policies;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;

class MilestonePolicy
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
    public function view(User $user, Milestone $milestone): bool
    {
        return $user->can('view', $milestone->project);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Project $project): bool
    {
        return $user->can('update', $project);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Milestone $milestone): bool
    {
        return $user->can('update', $milestone->project);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Milestone $milestone): bool
    {
        return $user->can('update', $milestone->project);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Milestone $milestone): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Milestone $milestone): bool
    {
        return false;
    }
}
