<?php

namespace App\Actions\Tasks;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateTask
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $creator, Project $project, array $attributes): Task
    {
        return DB::transaction(function () use ($creator, $project, $attributes): Task {
            $lockedProject = Project::query()->whereKey($project->getKey())->lockForUpdate()->firstOrFail();
            $number = $lockedProject->next_task_number;
            $lockedProject->increment('next_task_number');

            $task = $lockedProject->tasks()->create([
                ...$attributes,
                'number' => $number,
                'identifier' => $lockedProject->identifier.'-'.$number,
                'status' => TaskStatus::Backlog,
                'requester_id' => $attributes['requester_id'] ?? $creator->getKey(),
                'created_by_id' => $creator->getKey(),
                'assignee_id' => $attributes['assignee_id'] ?? $lockedProject->default_assignee_id,
            ]);
            $task->labels()->sync($attributes['label_ids'] ?? []);

            $this->recordAuditLog->handle(
                auditable: $task,
                action: 'task.created',
                actor: $creator,
                workspace: $lockedProject->workspace,
                after: [
                    'identifier' => $task->identifier,
                    'title' => $task->title,
                    'status' => TaskStatus::Backlog->value,
                ],
            );
            $this->recordActivity->handle($task, 'task.created', $creator, $lockedProject->workspace, 'Task dibuat.', ['identifier' => $task->identifier]);

            return $task->load(['project', 'assignee', 'requester', 'creator']);
        });
    }
}
