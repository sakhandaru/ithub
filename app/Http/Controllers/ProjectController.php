<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProject;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
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
        $projects = $workspace->projects()
            ->visibleTo($request->user())
            ->with(['unit', 'projectLead'])
            ->when($showArchived, fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->orderBy('name')
            ->get();

        return Inertia::render('projects/Index', [
            'workspace' => $workspace->only(['id', 'name']),
            'projects' => $projects->map(fn (Project $project): array => $this->projectSummary($project))->values(),
            'showArchived' => $showArchived,
        ]);
    }

    public function create(Workspace $workspace): Response
    {
        Gate::authorize('create', [Project::class, $workspace]);

        return Inertia::render('projects/Create', [
            'workspace' => $workspace->only(['id', 'name']),
            'units' => $workspace->units()->orderBy('name')->get(['id', 'name']),
            'members' => $workspace->memberships()->with('user')->orderBy('joined_at')->get()->map(fn ($membership): array => [
                'id' => $membership->user->id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
            ])->values(),
            'statuses' => collect(ProjectStatus::cases())->map(fn (ProjectStatus $status): array => ['value' => $status->value, 'label' => $status->label()]),
            'visibilities' => collect(ProjectVisibility::cases())->map(fn (ProjectVisibility $visibility): array => ['value' => $visibility->value, 'label' => $visibility->label()]),
        ]);
    }

    public function store(StoreProjectRequest $request, Workspace $workspace, CreateProject $createProject): RedirectResponse
    {
        $createProject->handle($request->user(), $workspace, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project berhasil dibuat.']);

        return to_route('workspaces.projects.index', $workspace);
    }

    public function show(Request $request, Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);
        $project->load(['unit', 'projectLead', 'defaultAssignee', 'creator', 'memberships.user', 'activityLogs.actor']);
        $project->loadCount('tasks');

        return Inertia::render('projects/Show', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => [
                ...$this->projectSummary($project),
                'description' => $project->description,
                'project_lead' => $project->projectLead?->only(['id', 'name', 'email']),
                'default_assignee' => $project->defaultAssignee?->only(['id', 'name', 'email']),
                'created_by' => $project->creator?->only(['id', 'name', 'email']),
                'members_count' => $project->memberships->count(),
                'tasks_count' => $project->tasks_count,
                'is_read_only' => $project->isArchived(),
            ],
            'activities' => $project->activityLogs->sortByDesc('created_at')->values()->map(fn ($activity): array => [
                'id' => $activity->id,
                'action' => $activity->action,
                'description' => $activity->description,
                'created_at' => $activity->created_at->toIso8601String(),
                'actor' => $activity->actor?->only(['id', 'name']),
            ]),
            'canManage' => Gate::allows('update', $project),
            'canRestore' => Gate::allows('restore', $project),
        ]);
    }

    public function dashboard(Request $request, Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);
        $tasks = $project->tasks();
        $activeStatuses = [TaskStatus::Request->value, TaskStatus::Backlog->value, TaskStatus::Todo->value, TaskStatus::InProgress->value, TaskStatus::InReview->value, TaskStatus::Blocked->value];
        $total = (clone $tasks)->count();
        $done = (clone $tasks)->where('status', TaskStatus::Done->value)->count();
        $statusCounts = (clone $tasks)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $overdue = (clone $tasks)->whereIn('status', $activeStatuses)->whereNotNull('due_date')->whereDate('due_date', '<', now())->count();
        $blocked = (clone $tasks)->where('status', TaskStatus::Blocked->value)->count();
        $withoutAssignee = (clone $tasks)->whereNull('assignee_id')->whereIn('status', $activeStatuses)->count();
        $withoutDeadline = (clone $tasks)->whereNull('due_date')->whereIn('status', $activeStatuses)->count();
        $project->load(['activityLogs.actor']);

        return Inertia::render('projects/Dashboard', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']),
            'stats' => [
                'total' => $total,
                'done' => $done,
                'progress' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
                'overdue' => $overdue,
                'blocked' => $blocked,
                'without_assignee' => $withoutAssignee,
                'without_deadline' => $withoutDeadline,
                'status_counts' => collect(TaskStatus::cases())->mapWithKeys(fn (TaskStatus $status): array => [$status->value => (int) ($statusCounts[$status->value] ?? 0)]),
            ],
            'activities' => $project->activityLogs->sortByDesc('created_at')->take(8)->values()->map(fn ($activity): array => [
                'id' => $activity->id,
                'description' => $activity->description,
                'action' => $activity->action,
                'created_at' => $activity->created_at->toIso8601String(),
                'actor' => $activity->actor?->only(['id', 'name']),
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function projectSummary(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'identifier' => $project->identifier,
            'unit' => $project->unit?->only(['id', 'name']),
            'project_lead' => $project->projectLead?->only(['id', 'name']),
            'visibility' => ProjectVisibility::from((string) $project->getRawOriginal('visibility'))->value,
            'visibility_label' => ProjectVisibility::from((string) $project->getRawOriginal('visibility'))->label(),
            'status' => ProjectStatus::from((string) $project->getRawOriginal('status'))->value,
            'status_label' => ProjectStatus::from((string) $project->getRawOriginal('status'))->label(),
            'start_date' => $project->start_date ? CarbonImmutable::parse((string) $project->getRawOriginal('start_date'))->toDateString() : null,
            'target_date' => $project->target_date ? CarbonImmutable::parse((string) $project->getRawOriginal('target_date'))->toDateString() : null,
            'archived' => $project->isArchived(),
        ];
    }
}
