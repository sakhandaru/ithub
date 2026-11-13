<?php

namespace App\Actions\Tasks;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Task;
use App\Models\TaskRelation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddTaskRelation
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $actor, Task $task, array $attributes): TaskRelation
    {
        return DB::transaction(function () use ($actor, $task, $attributes): TaskRelation {
            $relation = $task->relations()->create(['project_id' => $task->project_id, ...$attributes]);
            $this->recordAuditLog->handle(auditable: $task, action: 'task.relation_added', actor: $actor, workspace: $task->project->workspace, after: $relation->only(['related_task_id', 'type']));
            $this->recordActivity->handle($task, 'task.relation_added', $actor, $task->project->workspace, 'Relasi Task ditambahkan.', ['related_task_id' => $relation->related_task_id, 'type' => (string) $relation->getRawOriginal('type')]);

            return $relation;
        });
    }
}
