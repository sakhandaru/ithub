<?php

namespace App\Actions\Tasks;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransitionTask
{
    /** @var array<string, list<string>> */
    private const array AllowedTransitions = [
        'request' => ['backlog'],
        'backlog' => ['todo'],
        'todo' => ['in_progress'],
        'in_progress' => ['in_review', 'blocked', 'canceled'],
        'in_review' => ['done', 'in_progress', 'blocked', 'canceled'],
        'blocked' => ['in_progress', 'canceled'],
        'done' => ['in_progress'],
        'canceled' => [],
    ];

    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    /** @return list<TaskStatus> */
    public function allowedStatuses(TaskStatus $current): array
    {
        return array_map(
            static fn (string $status): TaskStatus => TaskStatus::from($status),
            self::AllowedTransitions[$current->value],
        );
    }

    public function handle(User $actor, Task $task, TaskStatus $target, ?string $reason = null, ?string $completionNote = null, ?string $evidenceUrl = null): Task
    {
        return DB::transaction(function () use ($actor, $task, $target, $reason, $completionNote, $evidenceUrl): Task {
            $task = Task::query()->whereKey($task->getKey())->lockForUpdate()->firstOrFail();
            $current = TaskStatus::from((string) $task->getRawOriginal('status'));
            if (! in_array($target->value, self::AllowedTransitions[$current->value], true)) {
                throw ValidationException::withMessages(['status' => "Transisi {$current->label()} ke {$target->label()} tidak diizinkan."]);
            }

            $isReopen = $current === TaskStatus::Done && $target === TaskStatus::InProgress;
            if (in_array($target, [TaskStatus::Blocked, TaskStatus::Canceled], true) && blank($reason)) {
                throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi untuk status Blocked atau Canceled.']);
            }
            if ($isReopen && blank($reason)) {
                throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi saat membuka kembali Task.']);
            }
            if ($target === TaskStatus::Done && blank($completionNote) && blank($evidenceUrl)) {
                throw ValidationException::withMessages(['completion_note' => 'Catatan penyelesaian atau URL bukti wajib diisi sebelum Done.']);
            }

            $attributes = [
                'status' => $target,
                'blocked_reason' => $target === TaskStatus::Blocked ? $reason : null,
                'canceled_reason' => $target === TaskStatus::Canceled ? $reason : null,
                'reopen_reason' => $isReopen ? $reason : null,
                'completion_note' => $target === TaskStatus::Done ? $completionNote : $task->completion_note,
                'evidence_url' => $target === TaskStatus::Done ? $evidenceUrl : $task->evidence_url,
                'completed_at' => $target === TaskStatus::Done ? now() : null,
            ];
            $task->update($attributes);

            $this->recordAuditLog->handle(
                auditable: $task,
                action: 'task.status.changed',
                actor: $actor,
                workspace: $task->project->workspace,
                before: ['status' => $current->value],
                after: ['status' => $target->value, 'reason' => $reason],
            );
            $this->recordActivity->handle($task, 'task.status.changed', $actor, $task->project->workspace, 'Status Task diperbarui.', ['from' => $current->value, 'to' => $target->value]);
            $recipients = collect([$task->assignee, $task->requester, $task->creator])->filter()->unique('id')->reject(fn (User $user): bool => $user->is($actor));
            $recipients->each(fn (User $user) => $user->notify(new TaskNotification('status', 'Status Task berubah', $task->identifier.' sekarang '.$target->label(), route('workspaces.projects.tasks.show', [$task->project->workspace, $task->project, $task]))));

            return $task->fresh();
        });
    }
}
