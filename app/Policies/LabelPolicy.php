<?php

namespace App\Policies;

use App\Models\Label;
use App\Models\Project;
use App\Models\User;

class LabelPolicy
{
    public function create(User $user, Project $project): bool
    {
        return $user->can('update', $project);
    }

    public function update(User $user, Label $label): bool
    {
        return $user->can('update', $label->project);
    }

    public function delete(User $user, Label $label): bool
    {
        return $user->can('update', $label->project);
    }
}
