<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskRelation;
use App\Models\User;

class TaskRelationPolicy
{
    public function create(User $user, Task $task): bool
    {
        return $user->can('update', $task);
    }

    public function delete(User $user, TaskRelation $relation): bool
    {
        return $user->can('update', $relation->task);
    }
}
