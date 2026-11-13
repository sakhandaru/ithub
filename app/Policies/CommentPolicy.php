<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;

class CommentPolicy
{
    public function view(User $user, Comment $comment): bool
    {
        return $user->can('view', $comment->task);
    }

    public function create(User $user, Task $task): bool
    {
        return $user->can('comment', $task);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->getKey() && $user->can('comment', $comment->task);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->getKey() || $user->can('update', $comment->task);
    }
}
