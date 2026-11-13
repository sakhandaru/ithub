<?php

namespace App\Actions\Comments;

use App\Actions\Activity\RecordActivity;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskNotification;
use Illuminate\Support\Facades\DB;

final class CreateComment
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(User $actor, Task $task, string $body): Comment
    {
        return DB::transaction(function () use ($actor, $task, $body): Comment {
            $comment = $task->comments()->create(['user_id' => $actor->getKey(), 'body' => $body]);
            $this->recordActivity->handle($task, 'task.comment.created', $actor, $task->project->workspace, 'Komentar ditambahkan.', ['comment_id' => $comment->id]);
            $recipients = collect([$task->assignee, $task->requester, $task->creator])->filter()->unique('id')->reject(fn (User $user): bool => $user->is($actor));
            $recipients->each(fn (User $user) => $user->notify(new TaskNotification('comment', 'Komentar baru pada Task', $task->identifier.' · '.$task->title, route('workspaces.projects.tasks.show', [$task->project->workspace, $task->project, $task]))));

            return $comment->load('user');
        });
    }
}
