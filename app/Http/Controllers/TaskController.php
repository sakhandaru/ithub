<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\AssignTask;
use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\UpdateTask;
use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request, Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);
        $status = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $search = trim($request->string('search')->toString());
        $sort = $request->string('sort', 'created_at')->toString();
        $direction = $request->string('direction', 'desc')->toString() === 'asc' ? 'asc' : 'desc';

        $tasks = $project->tasks()
            ->with(['assignee:id,name', 'requester:id,name', 'labels:id,name,color'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($priority !== '', fn ($query) => $query->where('priority', $priority))
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            }))
            ->orderBy(in_array($sort, ['created_at', 'due_date', 'identifier', 'title', 'status'], true) ? $sort : 'created_at', $direction)
            ->get();

        $selectedTask = null;
        $selectedId = $request->string('task')->toString();
        if ($selectedId !== '') {
            $selectedTask = $project->tasks()
                ->with(['assignee:id,name,email', 'requester:id,name,email', 'creator:id,name,email', 'labels:id,name,color'])
                ->whereKey($selectedId)
                ->first();
        }

        return Inertia::render('tasks/Index', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
            'tasks' => $tasks->map(fn (Task $task): array => $this->taskSummary($task))->values(),
            'selectedTask' => $selectedTask ? $this->taskSummary($selectedTask, true) : null,
            'filters' => ['status' => $status, 'priority' => $priority, 'search' => $search, 'sort' => $sort, 'direction' => $direction],
            'statuses' => collect(TaskStatus::cases())->map(fn (TaskStatus $item): array => ['value' => $item->value, 'label' => $item->label()]),
            'priorities' => collect(TaskPriority::cases())->map(fn (TaskPriority $item): array => ['value' => $item->value, 'label' => $item->label()]),
            'canCreate' => $request->user()->can('create', [Task::class, $project]),
            'canManage' => $request->user()->can('update', $selectedTask ?? $project),
        ]);
    }

    public function board(Request $request, Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('tasks/Board', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
            'tasks' => $project->tasks()->with(['assignee:id,name', 'labels:id,name,color'])->orderBy('created_at')->get()->map(fn (Task $task): array => $this->taskSummary($task))->values(),
            'statuses' => collect(TaskStatus::cases())->map(fn (TaskStatus $item): array => ['value' => $item->value, 'label' => $item->label()]),
            'canTransition' => $request->user()->can('transition', $project->tasks()->first() ?? new Task(['project_id' => $project->id])),
        ]);
    }

    public function show(Request $request, Workspace $workspace, Project $project, Task $task): Response
    {
        Gate::authorize('view', $task);
        $task->load(['assignee:id,name,email', 'requester:id,name,email', 'creator:id,name,email', 'labels:id,name,color', 'comments.user', 'attachments.uploader', 'activityLogs.actor']);
        $members = $project->memberships()
            ->with('user:id,name,email')
            ->whereIn('role', [ProjectRole::ProjectAdmin->value, ProjectRole::Contributor->value])
            ->get()
            ->map(fn ($membership): array => ['id' => $membership->user->id, 'name' => $membership->user->name, 'email' => $membership->user->email])
            ->values();

        return Inertia::render('tasks/Show', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
            'task' => $this->taskSummary($task, true),
            'comments' => $task->comments()->with('user')->get()->map(fn ($comment): array => [
                'id' => $comment->id,
                'body' => $comment->body,
                'edited_at' => $comment->getRawOriginal('edited_at') ? CarbonImmutable::parse((string) $comment->getRawOriginal('edited_at'))->toIso8601String() : null,
                'created_at' => $comment->created_at?->toIso8601String(),
                'user' => $comment->user?->only(['id', 'name', 'email']),
                'can_delete' => $comment->user_id === $request->user()->getKey() || $request->user()->can('update', $task),
            ])->values(),
            'attachments' => $task->attachments()->with('uploader')->get()->map(fn ($attachment): array => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'created_at' => $attachment->created_at?->toIso8601String(),
                'uploader' => $attachment->uploader?->only(['id', 'name']),
            ])->values(),
            'activities' => $task->activityLogs()->with('actor')->latest('created_at')->get()->map(fn ($activity): array => [
                'id' => $activity->id,
                'action' => $activity->action,
                'description' => $activity->description,
                'created_at' => $activity->created_at->toIso8601String(),
                'actor' => $activity->actor?->only(['id', 'name']),
            ])->values(),
            'members' => $members,
            'statuses' => collect(TaskStatus::cases())->map(fn (TaskStatus $item): array => ['value' => $item->value, 'label' => $item->label()]),
            'priorities' => collect(TaskPriority::cases())->map(fn (TaskPriority $item): array => ['value' => $item->value, 'label' => $item->label()]),
            'canUpdate' => $request->user()->can('update', $task),
            'canTransition' => $request->user()->can('transition', $task),
            'canAssign' => $request->user()->can('assign', $task),
            'canComment' => $request->user()->can('comment', $task),
            'canAttach' => $request->user()->can('attach', $task),
        ]);
    }

    public function store(StoreTaskRequest $request, Workspace $workspace, Project $project, CreateTask $createTask): RedirectResponse
    {
        $task = $createTask->handle($request->user(), $project, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => $task->identifier.' berhasil dibuat.']);

        return back();
    }

    public function update(UpdateTaskRequest $request, Workspace $workspace, Project $project, Task $task, UpdateTask $updateTask): RedirectResponse
    {
        $updateTask->handle($request->user(), $task, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Task berhasil diperbarui.']);

        return back();
    }

    public function assign(AssignTaskRequest $request, Workspace $workspace, Project $project, Task $task, AssignTask $assignTask): RedirectResponse
    {
        $assignee = $request->filled('assignee_id')
            ? User::query()->findOrFail($request->integer('assignee_id'))
            : null;
        $assignTask->handle($request->user(), $task, $assignee);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'PIC Task berhasil diperbarui.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function taskSummary(Task $task, bool $detail = false): array
    {
        $status = TaskStatus::from((string) $task->getRawOriginal('status'));
        $priority = $task->getRawOriginal('priority') ? TaskPriority::from((string) $task->getRawOriginal('priority')) : null;

        return [
            'id' => $task->id,
            'identifier' => $task->identifier,
            'title' => $task->title,
            'description' => $detail ? $task->description : null,
            'status' => $status->value,
            'status_label' => $status->label(),
            'priority' => $priority?->value,
            'priority_label' => $priority?->label(),
            'assignee' => $task->assignee?->only(['id', 'name', 'email']),
            'requester' => $task->requester?->only(['id', 'name', 'email']),
            'creator' => $detail ? $task->creator?->only(['id', 'name', 'email']) : null,
            'labels' => $task->labels->map(fn ($label): array => $label->only(['id', 'name', 'color']))->values(),
            'due_date' => $task->getRawOriginal('due_date') ? CarbonImmutable::parse((string) $task->getRawOriginal('due_date'))->toDateString() : null,
            'completed_at' => $task->getRawOriginal('completed_at') ? CarbonImmutable::parse((string) $task->getRawOriginal('completed_at'))->toIso8601String() : null,
            'completion_note' => $detail ? $task->completion_note : null,
            'evidence_url' => $detail ? $task->evidence_url : null,
            'blocked_reason' => $detail ? $task->blocked_reason : null,
            'canceled_reason' => $detail ? $task->canceled_reason : null,
            'reopen_reason' => $detail ? $task->reopen_reason : null,
            'created_at' => $task->created_at->toIso8601String(),
            'updated_at' => $task->updated_at->toIso8601String(),
            'is_completed' => $task->isCompleted(),
        ];
    }
}
