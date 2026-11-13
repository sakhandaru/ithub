<?php

namespace App\Actions\Tasks;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Enums\ProjectRole;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssignTask
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    public function handle(User $actor, Task $task, ?User $assignee): Task
    {
        return DB::transaction(function () use ($actor, $task, $assignee): Task {
            if ($assignee !== null) {
                $role = $task->project->memberships()
                    ->where('user_id', $assignee->getKey())
                    ->value('role');

                if (! in_array($role, [ProjectRole::ProjectAdmin->value, ProjectRole::Contributor->value], true)) {
                    throw ValidationException::withMessages(['assignee_id' => 'PIC Task harus Project Admin atau Contributor.']);
                }
            }

            $before = ['assignee_id' => $task->assignee_id];
            $task->update(['assignee_id' => $assignee?->getKey()]);
            $this->recordAuditLog->handle(
                auditable: $task,
                action: 'task.assignee.updated',
                actor: $actor,
                workspace: $task->project->workspace,
                before: $before,
                after: ['assignee_id' => $task->assignee_id],
            );
            $this->recordActivity->handle($task, 'task.assignee.updated', $actor, $task->project->workspace, 'PIC Task diperbarui.', ['assignee_id' => $task->assignee_id]);

            return $task->fresh(['assignee']);
        });
    }
}
