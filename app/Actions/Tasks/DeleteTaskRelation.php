<?php

namespace App\Actions\Tasks;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\TaskRelation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteTaskRelation
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    public function handle(User $actor, TaskRelation $relation): void
    {
        DB::transaction(function () use ($actor, $relation): void {
            $task = $relation->task;
            $this->recordAuditLog->handle(auditable: $task, action: 'task.relation_removed', actor: $actor, workspace: $task->project->workspace, before: $relation->only(['related_task_id', 'type']));
            $relation->delete();
            $this->recordActivity->handle($task, 'task.relation_removed', $actor, $task->project->workspace, 'Relasi Task dihapus.');
        });
    }
}
