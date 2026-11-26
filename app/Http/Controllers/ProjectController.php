<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProject;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request, Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);
        $showArchived = $request->boolean('archived');
        $createProjectOpen = $request->boolean('create');
        $activeUnit = $request->user()->activeUnit;
        $activeTaskStatuses = [
            TaskStatus::Backlog->value,
            TaskStatus::Todo->value,
            TaskStatus::InProgress->value,
            TaskStatus::Review->value,
            TaskStatus::Blocked->value,
        ];
        $projects = $workspace->projects()
            ->visibleTo($request->user())
            ->when($activeUnit?->workspace_id === $workspace->getKey(), fn ($query) => $query->whereBelongsTo($activeUnit))
            ->with(['unit', 'projectLead'])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', TaskStatus::Done->value),
                'tasks as blocked_tasks_count' => fn ($query) => $query->where('status', TaskStatus::Blocked->value),
                'tasks as overdue_tasks_count' => fn ($query) => $query
                    ->whereIn('status', $activeTaskStatuses)
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()),
            ])
            ->when($showArchived, fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->orderBy('name')
            ->get();

        return Inertia::render('projects/Index', [
            'workspace' => $workspace->only(['id', 'name']),
            'unit' => $activeUnit?->only(['id', 'name']),
            'projects' => $projects->map(fn (Project $project): array => $this->projectSummary($project))->values(),
            'showArchived' => $showArchived,
            'createProjectOpen' => $createProjectOpen,
            ...$this->projectFormOptions($workspace),
        ]);
    }

    public function create(Workspace $workspace): RedirectResponse
    {
        Gate::authorize('create', [Project::class, $workspace]);

        return to_route('workspaces.projects.index', [$workspace, 'create' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function projectFormOptions(Workspace $workspace): array
    {
        return [
            'units' => $workspace->units()->orderBy('name')->get(['id', 'name']),
            'members' => $workspace->memberships()->with('user')->orderBy('joined_at')->get()->map(fn ($membership): array => [
                'id' => $membership->user->id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
                'avatar' => $membership->user->avatar,
            ])->values(),
            'statuses' => collect(ProjectStatus::cases())->map(fn (ProjectStatus $status): array => ['value' => $status->value, 'label' => $status->label()]),
            'visibilities' => collect(ProjectVisibility::cases())->map(fn (ProjectVisibility $visibility): array => ['value' => $visibility->value, 'label' => $visibility->label()]),
        ];
    }

    public function store(StoreProjectRequest $request, Workspace $workspace, CreateProject $createProject): RedirectResponse
    {
        $createProject->handle($request->user(), $workspace, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project berhasil dibuat.']);

        return to_route('workspaces.projects.index', $workspace);
    }

    public function show(Workspace $workspace, Project $project): RedirectResponse
    {
        Gate::authorize('view', $project);

        return to_route('workspaces.projects.dashboard', [$workspace, $project]);
    }

    public function activity(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);
        $project->load('unit');

        return Inertia::render('projects/Activity', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => [
                ...$this->projectSummary($project),
                'archived_at' => $project->archived_at?->toIso8601String(),
            ],
            'activities' => $this->projectActivityQuery($workspace, $project)
                ->with(['actor:id,name', 'subject'])
                ->latest('created_at')
                ->get()
                ->map(fn ($activity): array => [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'created_at' => $activity->created_at->toIso8601String(),
                    'actor' => $activity->actor?->only(['id', 'name', 'avatar']),
                    'entity' => $activity->entityData($workspace),
                ])
                ->values(),
        ]);
    }

    public function cycles(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/Cycles', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
        ]);
    }

    public function modules(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/Modules', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
        ]);
    }

    public function dashboard(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);
        $project->load('unit');

        $activeStatuses = [
            TaskStatus::Backlog->value,
            TaskStatus::Todo->value,
            TaskStatus::InProgress->value,
            TaskStatus::Review->value,
            TaskStatus::Blocked->value,
        ];
        $statusCounts = $project->tasks()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $total = (int) $statusCounts->sum();
        $done = (int) ($statusCounts[TaskStatus::Done->value] ?? 0);
        $today = CarbonImmutable::today();
        $overdue = $project->tasks()->whereIn('status', $activeStatuses)->whereNotNull('due_date')->whereDate('due_date', '<', $today)->count();
        $blocked = (int) ($statusCounts[TaskStatus::Blocked->value] ?? 0);
        $withoutAssignee = $project->tasks()->whereIn('status', $activeStatuses)->whereNull('assignee_id')->count();
        $withoutDeadline = $project->tasks()->whereIn('status', $activeStatuses)->whereNull('due_date')->count();
        $health = $overdue > 0 || $blocked > 0 ? 'off_track' : ($withoutAssignee > 0 || $withoutDeadline > 0 ? 'at_risk' : 'on_track');

        return Inertia::render('projects/Dashboard', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => [
                ...$this->projectSummary($project),
                'archived_at' => $project->archived_at?->toIso8601String(),
            ],
            'stats' => [
                'total' => $total,
                'done' => $done,
                'progress' => $total === 0 ? 0 : (int) round(($done / $total) * 100),
                'overdue' => $overdue,
                'blocked' => $blocked,
                'without_assignee' => $withoutAssignee,
                'without_deadline' => $withoutDeadline,
                'status_counts' => collect(TaskStatus::cases())->map(fn (TaskStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'count' => (int) ($statusCounts[$status->value] ?? 0),
                ])->values(),
            ],
            'health' => $health,
            'next_milestone' => $project->milestones()
                ->whereDate('target_date', '>=', $today)
                ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', TaskStatus::Done->value)])
                ->orderBy('target_date')
                ->orderBy('name')
                ->first()
                ?->only(['id', 'name', 'target_date', 'tasks_count', 'completed_tasks_count']),
            'next_tasks' => $project->tasks()
                ->with('assignee:id,name,avatar')
                ->whereIn('status', $activeStatuses)
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'identifier', 'title', 'status', 'due_date', 'assignee_id'])
                ->map(fn ($task): array => [
                    'id' => $task->id,
                    'identifier' => $task->identifier,
                    'title' => $task->title,
                    'status' => $task->status->value,
                    'status_label' => $task->status->label(),
                    'due_date' => $task->due_date?->format('Y-m-d'),
                    'assignee' => $task->assignee?->only(['id', 'name', 'avatar']),
                ])
                ->values(),
            'activities' => $this->projectActivityQuery($workspace, $project)
                ->with(['actor:id,name', 'subject'])
                ->latest('created_at')
                ->limit(8)
                ->get()
                ->map(fn ($activity): array => [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'created_at' => $activity->created_at->toIso8601String(),
                    'actor' => $activity->actor?->only(['id', 'name', 'avatar']),
                    'entity' => $activity->entityData($workspace),
                ])
                ->values(),
        ]);
    }

    private function projectActivityQuery(Workspace $workspace, Project $project): Builder
    {
        $taskIds = $project->tasks()->select('id');

        return ActivityLog::query()
            ->whereBelongsTo($workspace)
            ->where(function ($query) use ($project, $taskIds): void {
                $query->where(function ($projectQuery) use ($project): void {
                    $projectQuery->where('subject_type', $project->getMorphClass())->where('subject_id', $project->getKey());
                })->orWhere(function ($taskQuery) use ($taskIds): void {
                    $taskQuery->where('subject_type', (new Task)->getMorphClass())->whereIn('subject_id', $taskIds);
                });
            });
    }

    /** @return array<string, mixed> */
    private function projectSummary(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'icon' => $project->icon,
            'identifier' => $project->identifier,
            'unit' => $project->unit?->only(['id', 'name']),
            'project_lead' => $project->projectLead?->only(['id', 'name', 'avatar']),
            'visibility' => ProjectVisibility::from((string) $project->getRawOriginal('visibility'))->value,
            'visibility_label' => ProjectVisibility::from((string) $project->getRawOriginal('visibility'))->label(),
            'status' => $project->isArchived() ? 'archived' : ProjectStatus::from((string) $project->getRawOriginal('status'))->value,
            'status_label' => $project->isArchived() ? 'Diarsipkan' : ProjectStatus::from((string) $project->getRawOriginal('status'))->label(),
            'start_date' => $project->start_date ? CarbonImmutable::parse((string) $project->getRawOriginal('start_date'))->toDateString() : null,
            'target_date' => $project->target_date ? CarbonImmutable::parse((string) $project->getRawOriginal('target_date'))->toDateString() : null,
            'archived' => $project->isArchived(),
            'tasks_count' => (int) ($project->getAttribute('tasks_count') ?? 0),
            'completed_tasks_count' => (int) ($project->getAttribute('completed_tasks_count') ?? 0),
            'blocked_tasks_count' => (int) ($project->getAttribute('blocked_tasks_count') ?? 0),
            'overdue_tasks_count' => (int) ($project->getAttribute('overdue_tasks_count') ?? 0),
        ];
    }
}
