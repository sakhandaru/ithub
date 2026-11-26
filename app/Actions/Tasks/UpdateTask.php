<?php

namespace App\Actions\Tasks;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateTask
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $actor, Task $task, array $attributes): Task
    {
        return DB::transaction(function () use ($actor, $task, $attributes): Task {
            $before = $task->only(array_keys($attributes));
            $task->update($attributes);

            $this->recordAuditLog->handle(
                auditable: $task,
                action: 'task.updated',
                actor: $actor,
                workspace: $task->project->workspace,
                before: $before,
                after: $task->only(array_keys($attributes)),
            );
            $this->recordActivity->handle($task, 'task.updated', $actor, $task->project->workspace, 'Task diperbarui.', ['fields' => array_keys($attributes)]);

            return $task->fresh(['project', 'assignee', 'requester', 'creator']);
        });
    }
}
