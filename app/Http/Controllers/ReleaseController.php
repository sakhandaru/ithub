<?php

namespace App\Http\Controllers;

use App\Enums\ReleaseStatus;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreReleaseRequest;
use App\Models\Project;
use App\Models\Release;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseController extends Controller
{
    public function index(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/Releases', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']) + ['archived' => $project->isArchived()],
            'releases' => $project->releases()->withCount('tasks')->orderByDesc('target_date')->orderBy('name')->get()->map(fn (Release $release): array => [
                'id' => $release->id, 'name' => $release->name, 'description' => $release->description,
                'target_date' => $release->target_date?->format('Y-m-d'), 'status' => $release->status->value, 'tasks_count' => $release->tasks_count,
            ])->values(),
            'statuses' => collect(ReleaseStatus::cases())->map(fn (ReleaseStatus $status): array => ['value' => $status->value, 'label' => str($status->value)->replace('_', ' ')->title()->toString()])->values(),
            'canManage' => Gate::allows('update', $project),
        ]);
    }

    public function store(StoreReleaseRequest $request, Workspace $workspace, Project $project): RedirectResponse
    {
        $project->releases()->create($request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => 'Rilis dibuat.']);
    }

    public function show(Workspace $workspace, Project $project, Release $release): Response
    {
        Gate::authorize('view', $release);

        $tasks = $release->tasks()
            ->with(['assignee:id,name'])
            ->orderBy('status')
            ->orderBy('due_date')
            ->orderBy('identifier')
            ->get();
        $completedTasks = $tasks->whereIn('status', [TaskStatus::Done->value])->count();

        return Inertia::render('projects/ReleaseShow', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']) + ['archived' => $project->isArchived()],
            'release' => [
                'id' => $release->id,
                'name' => $release->name,
                'description' => $release->description,
                'target_date' => $release->target_date?->format('Y-m-d'),
                'status' => $release->status->value,
                'tasks_count' => $tasks->count(),
                'completed_tasks_count' => $completedTasks,
            ],
            'tasks' => $tasks->map(fn ($task): array => [
                'id' => $task->id,
                'identifier' => $task->identifier,
                'title' => $task->title,
                'status' => $task->status->value,
                'status_label' => $task->status->label(),
                'assignee' => $task->assignee?->only(['id', 'name']),
                'due_date' => $task->due_date?->format('Y-m-d'),
            ])->values(),
            'modules' => $release->modules()->withCount('tasks')->orderBy('name')->get(['id', 'name', 'description'])->map(fn ($module): array => [
                'id' => $module->id, 'name' => $module->name, 'description' => $module->description, 'tasks_count' => $module->tasks_count,
            ])->values(),
            'milestones' => $release->milestones()->withCount('tasks')->orderBy('target_date')->orderBy('name')->get(['id', 'name', 'target_date'])->map(fn ($milestone): array => [
                'id' => $milestone->id, 'name' => $milestone->name, 'target_date' => $milestone->target_date?->format('Y-m-d'), 'tasks_count' => $milestone->tasks_count,
            ])->values(),
        ]);
    }
}
