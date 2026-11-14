<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\SavedView;
use App\Models\User;

class SavedViewPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SavedView $savedView): bool
    {
        return $savedView->user->is($user) && $user->can('view', $savedView->project);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Project $project): bool
    {
        return $user->can('view', $project);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SavedView $savedView): bool
    {
        return $this->view($user, $savedView);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SavedView $savedView): bool
    {
        return $this->view($user, $savedView);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SavedView $savedView): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SavedView $savedView): bool
    {
        return false;
    }
}
