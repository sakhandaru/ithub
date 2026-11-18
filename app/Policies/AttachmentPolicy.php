<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        return $attachment->task_id !== null
            ? $user->can('view', $attachment->task)
            : $user->can('view', $attachment->project);
    }

    public function create(User $user, Task $task): bool
    {
        return $user->can('attach', $task);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        $parent = $attachment->task ?? $attachment->project;

        return $attachment->uploaded_by_id === $user->getKey() || $user->can('update', $parent);
    }
}
