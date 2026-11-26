<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\AssignTask;
use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\TransitionTask;
use App\Actions\Tasks\UpdateTask;
use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskRelationType;
use App\Enums\TaskStatus;
use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\BulkUpdateTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\SavedView;
use App\Models\Task;
use App\Models\TaskRelation;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request, Workspace $workspace, Project $project, TransitionTask $transitionTask): Response
    {
        Gate::authorize('view', $project);
        $view = $request->string('view', 'board')->toString();
        $view = in_array($view, ['list', 'board', 'calendar', 'gantt'], true) ? $view : 'board';
        $status = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $assignee = $request->string('assignee')->toString();
        $requester = $request->string('requester')->toString();
        $milestone = $request->string('milestone')->toString();
        $module = $request->string('module')->toString();
        $release = $request->string('release')->toString();
        $preset = $request->string('preset')->toString();
        $search = trim($request->string('search')->toString());
        $sort = $request->string('sort', 'created_at')->toString();
        $direction = $request->string('direction', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        $activeSavedView = SavedView::query()
            ->forProject($project)
            ->forUser($request->user())
            ->whereKey($request->string('view')->toString())
            ->first();
        $savedViewWarnings = [];

        if ($activeSavedView) {
            if ($status !== '' && ! collect(TaskStatus::cases())->contains(fn (TaskStatus $item): bool => $item->value === $status)) {
                $savedViewWarnings[] = 'status';
                $status = '';
            }

            if ($priority !== '' && ! collect(TaskPriority::cases())->contains(fn (TaskPriority $item): bool => $item->value === $priority)) {
                $savedViewWarnings[] = 'priority';
                $priority = '';
            }

            if ($assignee !== '' && $assignee !== 'none' && ! $project->memberships()->whereIn('role', [ProjectRole::ProjectAdmin->value, ProjectRole::Contributor->value])->where('user_id', $assignee)->exists()) {
                $savedViewWarnings[] = 'assignee';
                $assignee = '';
            }

            if ($milestone !== '' && $milestone !== 'none' && ! $project->milestones()->whereKey($milestone)->exists()) {
                $savedViewWarnings[] = 'milestone';
                $milestone = '';
            }
            if ($module !== '' && $module !== 'none' && ! $project->modules()->whereKey($module)->exists()) {
                $savedViewWarnings[] = 'module';
                $module = '';
            }
            if ($release !== '' && $release !== 'none' && ! $project->releases()->whereKey($release)->exists()) {
                $savedViewWarnings[] = 'release';
                $release = '';
            }
        }

        $tasks = $project->tasks()
            ->with(['assignee:id,name,avatar', 'requester:id,name,avatar', 'relations.relatedTask:id,identifier,title'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($priority !== '', fn ($query) => $query->where('priority', $priority))
            ->when($assignee !== '', fn ($query) => $query->where('assignee_id', $assignee === 'none' ? null : $assignee))
            ->when($requester !== '', fn ($query) => $query->where('requester_id', $requester))
            ->when($milestone !== '', fn ($query) => $query->where('milestone_id', $milestone === 'none' ? null : $milestone))
            ->when($module !== '', fn ($query) => $query->where('module_id', $module === 'none' ? null : $module))
            ->when($release !== '', fn ($query) => $query->where('release_id', $release === 'none' ? null : $release))
            ->when($request->boolean('no_deadline'), fn ($query) => $query->whereNull('due_date'))
            ->when($request->boolean('no_evidence'), fn ($query) => $query->whereNull('completion_note')->whereNull('evidence_url'))
            ->when($preset === 'mine', fn ($query) => $query->where('assignee_id', $request->user()->getKey()))
            ->when($preset === 'overdue', fn ($query) => $query->whereNotNull('due_date')->whereDate('due_date', '<', now())->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Canceled->value]))
            ->when($preset === 'week', fn ($query) => $query->whereBetween('due_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Canceled->value]))
            ->when($preset === 'no_deadline', fn ($query) => $query->whereNull('due_date'))
            ->when($preset === 'blocked', fn ($query) => $query->where('status', TaskStatus::Blocked->value))
            ->when($preset === 'unassigned', fn ($query) => $query->whereNull('assignee_id'))
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            }))
            ->orderBy(in_array($sort, ['created_at', 'due_date', 'identifier', 'title', 'status'], true) ? $sort : 'created_at', $direction)
            ->get();

        $selectedTask = null;
        $taskDetail = null;
        $selectedId = $request->string('task')->toString();
        if ($selectedId !== '') {
            $selectedTask = $project->tasks()
                ->whereKey($selectedId)
                ->first();

            if ($selectedTask) {
                Gate::authorize('view', $selectedTask);
                $taskDetail = $this->taskDetailProps($request, $workspace, $project, $selectedTask, $transitionTask);
            }
        }

        $scheduledTasks = $tasks->filter(fn (Task $task): bool => $task->getRawOriginal('due_date') !== null)->values();

        return Inertia::render('tasks/Index', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
            'tasks' => $tasks->map(fn (Task $task): array => $this->taskSummary($task))->values(),
            'scheduledTasks' => $scheduledTasks->map(fn (Task $task): array => $this->taskSummary($task))->values(),
            'selectedTask' => $selectedTask ? $this->taskSummary($selectedTask, true) : null,
            'taskDetail' => $taskDetail,
            'editableTaskIds' => $tasks->filter(fn (Task $task): bool => $request->user()->can('update', $task))->modelKeys(),
            'filters' => ['status' => $status, 'priority' => $priority, 'assignee' => $assignee, 'requester' => $requester, 'milestone' => $milestone, 'module' => $module, 'release' => $release, 'preset' => in_array($preset, ['mine', 'week', 'overdue', 'no_deadline', 'blocked', 'unassigned'], true) ? $preset : '', 'no_deadline' => $request->boolean('no_deadline'), 'no_evidence' => $request->boolean('no_evidence'), 'search' => $search, 'sort' => $sort, 'direction' => $direction],
            'statuses' => collect(TaskStatus::cases())->map(fn (TaskStatus $item): array => ['value' => $item->value, 'label' => $item->label()]),
            'priorities' => collect(TaskPriority::cases())->map(fn (TaskPriority $item): array => ['value' => $item->value, 'label' => $item->label()]),
            'filterMembers' => $project->memberships()->with('user:id,name,avatar')->whereIn('role', [ProjectRole::ProjectAdmin->value, ProjectRole::Contributor->value])->get()->map(fn ($membership): array => ['id' => $membership->user->id, 'name' => $membership->user->name, 'avatar' => $membership->user->avatar])->values(),
            'filterMilestones' => $project->milestones()->orderBy('name')->get(['id', 'name']),
            'filterModules' => $project->modules()->orderBy('name')->get(['id', 'name']),
            'filterReleases' => $project->releases()->orderByDesc('target_date')->orderBy('name')->get(['id', 'name']),
            'savedViews' => $project->savedViews()
                ->forUser($request->user())
                ->orderBy('name')
                ->get()
                ->map(fn (SavedView $savedView): array => [
                    'id' => $savedView->id,
                    'name' => $savedView->name,
                    'filters' => $savedView->filters,
                    'grouping' => $savedView->grouping,
                    'compact' => $savedView->compact,
                ])
                ->values(),
            'activeSavedViewId' => $activeSavedView?->id,
            'savedViewWarnings' => $savedViewWarnings,
            'canCreate' => $request->user()->can('create', [Task::class, $project]),
            'canManage' => $request->user()->can('update', $selectedTask ?? $project),
            'canBulkUpdate' => $request->user()->can('update', $project),
            'view' => $view,
            'milestones' => $project->milestones()->orderBy('target_date')->orderBy('name')->get(['id', 'name', 'target_date']),
            'modules' => $project->modules()->orderBy('name')->get(['id', 'name', 'description']),
            'releases' => $project->releases()->orderByDesc('target_date')->orderBy('name')->get(['id', 'name', 'status', 'target_date']),
            'allowedTransitions' => $tasks->mapWithKeys(function (Task $task) use ($transitionTask): array {
                $current = TaskStatus::from((string) $task->getRawOriginal('status'));

                return [$task->id => array_map(static fn (TaskStatus $status): string => $status->value, $transitionTask->allowedStatuses($current))];
            })->all(),
        ]);
    }

    public function boardRedirect(Workspace $workspace, Project $project): RedirectResponse
    {
        return to_route('workspaces.projects.tasks.index', [$workspace, $project, 'view' => 'board']);
    }

    public function calendarRedirect(Workspace $workspace, Project $project): RedirectResponse
    {
        return to_route('workspaces.projects.tasks.index', [$workspace, $project, 'view' => 'calendar']);
    }

    public function ganttRedirect(Workspace $workspace, Project $project): RedirectResponse
    {
        return to_route('workspaces.projects.tasks.index', [$workspace, $project, 'view' => 'gantt']);
    }

    public function board(Request $request, Workspace $workspace, Project $project, TransitionTask $transitionTask): Response
    {
        Gate::authorize('view', $project);

        $status = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $assignee = $request->string('assignee')->toString();
        $requester = $request->string('requester')->toString();
        $milestone = $request->string('milestone')->toString();
        $module = $request->string('module')->toString();
        $release = $request->string('release')->toString();
        $preset = $request->string('preset')->toString();
        $search = trim($request->string('search')->toString());
        $sort = $request->string('sort', 'created_at')->toString();
        $direction = $request->string('direction', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        $tasks = $project->tasks()
            ->with(['assignee:id,name,avatar'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($priority !== '', fn ($query) => $query->where('priority', $priority))
            ->when($assignee !== '', fn ($query) => $query->where('assignee_id', $assignee === 'none' ? null : $assignee))
            ->when($requester !== '', fn ($query) => $query->where('requester_id', $requester))
            ->when($milestone !== '', fn ($query) => $query->where('milestone_id', $milestone === 'none' ? null : $milestone))
            ->when($module !== '', fn ($query) => $query->where('module_id', $module === 'none' ? null : $module))
            ->when($release !== '', fn ($query) => $query->where('release_id', $release === 'none' ? null : $release))
            ->when($request->boolean('no_deadline'), fn ($query) => $query->whereNull('due_date'))
            ->when($request->boolean('no_evidence'), fn ($query) => $query->whereNull('completion_note')->whereNull('evidence_url'))
            ->when($preset === 'mine', fn ($query) => $query->where('assignee_id', $request->user()->getKey()))
            ->when($preset === 'overdue', fn ($query) => $query->whereNotNull('due_date')->whereDate('due_date', '<', now())->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Canceled->value]))
            ->when($preset === 'blocked', fn ($query) => $query->where('status', TaskStatus::Blocked->value))
            ->when($preset === 'unassigned', fn ($query) => $query->whereNull('assignee_id'))
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%");
            }))
            ->orderBy(in_array($sort, ['created_at', 'due_date', 'identifier', 'title', 'status'], true) ? $sort : 'created_at', $direction)
            ->get();

        return Inertia::render('tasks/Board', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
            'tasks' => $tasks->map(fn (Task $task): array => $this->taskSummary($task))->values(),
            'statuses' => collect(TaskStatus::cases())->map(fn (TaskStatus $item): array => ['value' => $item->value, 'label' => $item->label()]),
            'canTransition' => $request->user()->can('transition', $project->tasks()->first() ?? new Task(['project_id' => $project->id])),
            'filters' => ['status' => $status, 'priority' => $priority, 'assignee' => $assignee, 'requester' => $requester, 'milestone' => $milestone, 'module' => $module, 'preset' => in_array($preset, ['mine', 'overdue', 'blocked', 'unassigned'], true) ? $preset : '', 'no_deadline' => $request->boolean('no_deadline'), 'no_evidence' => $request->boolean('no_evidence'), 'search' => $search, 'sort' => $sort, 'direction' => $direction],
            'allowedTransitions' => $tasks->mapWithKeys(function (Task $task) use ($transitionTask): array {
                $current = TaskStatus::from((string) $task->getRawOriginal('status'));

                return [$task->id => array_map(static fn (TaskStatus $status): string => $status->value, $transitionTask->allowedStatuses($current))];
            })->all(),
        ]);
    }

    public function calendar(Request $request, Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        $tasks = $project->tasks()
            ->with(['assignee:id,name,avatar', 'requester:id,name,avatar', 'relations.relatedTask:id,identifier,title'])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->orderBy('identifier')
            ->get();

        return Inertia::render('tasks/Calendar', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
            'tasks' => $tasks->map(fn (Task $task): array => $this->taskSummary($task))->values(),
        ]);
    }

    public function gantt(Request $request, Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        $tasks = $project->tasks()
            ->with(['assignee:id,name,avatar', 'requester:id,name,avatar'])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->orderBy('identifier')
            ->get();

        return Inertia::render('tasks/Gantt', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
            'tasks' => $tasks->map(fn (Task $task): array => $this->taskSummary($task))->values(),
            'milestones' => $project->milestones()->orderBy('target_date')->orderBy('name')->get(['id', 'name', 'target_date']),
            'modules' => $project->modules()->orderBy('name')->get(['id', 'name', 'description']),
            'releases' => $project->releases()->orderByDesc('target_date')->orderBy('name')->get(['id', 'name', 'status', 'target_date']),
        ]);
    }

    public function show(Workspace $workspace, Project $project, Task $task): RedirectResponse
    {
        Gate::authorize('view', $task);

        return to_route('workspaces.projects.tasks.index', [
            'workspace' => $workspace,
            'project' => $project,
            'task' => $task->getKey(),
        ]);
    }

    public function peek(Request $request, Workspace $workspace, Project $project, Task $task, TransitionTask $transitionTask): JsonResponse
    {
        Gate::authorize('view', $task);

        return response()->json($this->taskDetailProps($request, $workspace, $project, $task, $transitionTask));
    }

    /** @return array<string, mixed> */
    private function taskDetailProps(Request $request, Workspace $workspace, Project $project, Task $task, TransitionTask $transitionTask): array
    {
        $task->load(['assignee:id,name,email,avatar', 'requester:id,name,email,avatar', 'creator:id,name,email,avatar', 'comments.user', 'attachments.uploader', 'activityLogs.actor', 'milestone', 'module', 'release', 'relations.relatedTask']);
        $members = $project->memberships()
            ->with('user:id,name,email')
            ->whereIn('role', [ProjectRole::ProjectAdmin->value, ProjectRole::Contributor->value])
            ->get()
            ->map(fn ($membership): array => ['id' => $membership->user->id, 'name' => $membership->user->name, 'email' => $membership->user->email])
            ->values();

        return [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
            'task' => $this->taskSummary($task, true),
            'milestone' => $task->milestone?->only(['id', 'name', 'target_date']),
            'module' => $task->module?->only(['id', 'name', 'description']),
            'milestones' => $project->milestones()->orderBy('target_date')->orderBy('name')->get(['id', 'name', 'target_date']),
            'releases' => $project->releases()->orderByDesc('target_date')->orderBy('name')->get(['id', 'name', 'status', 'target_date']),
            'relatedTasks' => $project->tasks()->where('tasks.id', '!=', $task->id)->orderBy('number')->get(['id', 'identifier', 'title', 'status'])->map(fn (Task $relatedTask): array => ['id' => $relatedTask->id, 'identifier' => $relatedTask->identifier, 'title' => $relatedTask->title, 'status' => $relatedTask->status->value, 'status_label' => $relatedTask->status->label()])->values(),
            'relations' => $task->relations->map(fn (TaskRelation $relation): array => ['id' => $relation->id, 'type' => (string) $relation->getRawOriginal('type'), 'type_label' => TaskRelationType::from((string) $relation->getRawOriginal('type'))->label(), 'related_task' => $this->taskSummary($relation->relatedTask)])->values(),
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
                'uploader' => $attachment->uploader?->only(['id', 'name', 'avatar']),
            ])->values(),
            'activities' => $task->activityLogs()->with('actor')->latest('created_at')->get()->map(fn ($activity): array => [
                'id' => $activity->id,
                'action' => $activity->action,
                'description' => $activity->description,
                'created_at' => $activity->created_at->toIso8601String(),
                'actor' => $activity->actor?->only(['id', 'name', 'avatar']),
            ])->values(),
            'members' => $members,
            'statuses' => collect(TaskStatus::cases())->map(fn (TaskStatus $item): array => ['value' => $item->value, 'label' => $item->label()]),
            'transitionStatuses' => collect($transitionTask->allowedStatuses(TaskStatus::from((string) $task->getRawOriginal('status'))))->map(fn (TaskStatus $item): array => ['value' => $item->value, 'label' => $item->label()])->values(),
            'priorities' => collect(TaskPriority::cases())->map(fn (TaskPriority $item): array => ['value' => $item->value, 'label' => $item->label()]),
            'canUpdate' => $request->user()->can('update', $task),
            'canTransition' => $request->user()->can('transition', $task),
            'canAssign' => $request->user()->can('assign', $task),
            'canComment' => $request->user()->can('comment', $task),
            'canAttach' => $request->user()->can('attach', $task),
            'canManageStructure' => $request->user()->can('update', $task),
        ];
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

    public function bulkUpdate(BulkUpdateTasksRequest $request, Workspace $workspace, Project $project, UpdateTask $updateTask, AssignTask $assignTask): RedirectResponse
    {
        $validated = $request->validated();
        $actor = $request->user();
        $assignee = array_key_exists('assignee_id', $validated) && $validated['assignee_id'] !== null
            ? User::query()->whereKey($validated['assignee_id'])->firstOrFail()
            : null;

        foreach ($project->tasks()->whereIn('id', $validated['task_ids'])->get() as $task) {
            Gate::authorize('update', $task);
            if (array_key_exists('priority', $validated)) {
                $updateTask->handle($actor, $task, ['priority' => $validated['priority']]);
            }
            if (array_key_exists('assignee_id', $validated)) {
                $assignTask->handle($actor, $task, $assignee);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => count($validated['task_ids']).' Task berhasil diperbarui.']);

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
            'expected_outcome' => $detail ? $task->expected_outcome : null,
            'status' => $status->value,
            'status_label' => $status->label(),
            'priority' => $priority?->value,
            'priority_label' => $priority?->label(),
            'assignee' => $task->assignee?->only(['id', 'name', 'email', 'avatar']),
            'requester' => $task->requester?->only(['id', 'name', 'email', 'avatar']),
            'creator' => $detail ? $task->creator?->only(['id', 'name', 'email']) : null,
            'start_date' => $task->getRawOriginal('start_date') ? CarbonImmutable::parse((string) $task->getRawOriginal('start_date'))->toDateString() : null,
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
            'milestone' => $task->milestone?->only(['id', 'name', 'target_date']),
            'module' => $task->module?->only(['id', 'name', 'description']),
            'release' => $task->release?->only(['id', 'name', 'status', 'target_date']),
            'relations' => $task->relationLoaded('relations')
                ? $task->relations->map(fn (TaskRelation $relation): array => [
                    'id' => $relation->id,
                    'type' => (string) $relation->getRawOriginal('type'),
                    'type_label' => TaskRelationType::from((string) $relation->getRawOriginal('type'))->label(),
                    'related_task' => $relation->relatedTask?->only(['id', 'identifier', 'title']),
                ])->filter(fn (array $relation): bool => $relation['related_task'] !== null)->values()->all()
                : [],
        ];
    }
}
