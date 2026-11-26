<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\Project;
use App\Models\User;

class ModulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Module $module): bool
    {
        return $user->can('view', $module->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->can('update', $project);
    }

    public function update(User $user, Module $module): bool
    {
        return $user->can('update', $module->project);
    }

    public function delete(User $user, Module $module): bool
    {
        return $user->can('update', $module->project);
    }

    public function restore(User $user, Module $module): bool
    {
        return false;
    }

    public function forceDelete(User $user, Module $module): bool
    {
        return false;
    }
}
