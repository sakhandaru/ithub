<?php

namespace App\Actions\Comments;

use App\Actions\Activity\RecordActivity;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateComment
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(User $actor, Task $task, string $body): Comment
    {
        return DB::transaction(function () use ($actor, $task, $body): Comment {
            $comment = $task->comments()->create(['user_id' => $actor->getKey(), 'body' => $body]);
            $this->recordActivity->handle($task, 'task.comment.created', $actor, $task->project->workspace, 'Komentar ditambahkan.', ['comment_id' => $comment->id]);

            return $comment->load('user');
        });
    }
}
