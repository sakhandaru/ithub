<?php

namespace App\Actions\Comments;

use App\Actions\Activity\RecordActivity;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteComment
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(User $actor, Comment $comment): void
    {
        DB::transaction(function () use ($actor, $comment): void {
            $task = $comment->task;
            $comment->delete();
            $this->recordActivity->handle($task, 'task.comment.deleted', $actor, $task->project->workspace, 'Komentar dihapus.', ['comment_id' => $comment->id]);
        });
    }
}
