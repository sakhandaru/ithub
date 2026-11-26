<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UnitAnalyticsController extends Controller
{
    public function __invoke(Request $request, Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);

        $unit = $request->user()->activeUnit()->whereBelongsTo($workspace)->first();
        $projects = $unit
            ? $unit->projects()
                ->visibleTo($request->user())
                ->whereNull('archived_at')
                ->withCount('tasks')
                ->orderBy('name')
                ->get()
            : collect();
        $tasks = $projects->load('tasks')->pluck('tasks')->flatten();
        $activeTasks = $tasks->filter(fn ($task): bool => ! in_array($task->status->value, [TaskStatus::Done->value, TaskStatus::Canceled->value], true));
        $now = CarbonImmutable::now($workspace->timezone);
        $statusCounts = collect(TaskStatus::cases())->mapWithKeys(fn (TaskStatus $status): array => [
            $status->value => [
                'label' => $status->label(),
                'count' => $tasks->where('status', $status)->count(),
            ],
        ]);
        $activities = ActivityLog::query()
            ->whereBelongsTo($workspace)
            ->with('actor:id,name')
            ->latest('created_at')
            ->limit(8)
            ->get();

        return Inertia::render('unit/Analytics', [
            'workspace' => $workspace->only(['id', 'name', 'timezone']),
            'unit' => $unit?->only(['id', 'name']),
            'stats' => [
                'projects' => $projects->count(),
                'tasks' => $tasks->count(),
                'active_tasks' => $activeTasks->count(),
                'done_tasks' => $tasks->where('status', TaskStatus::Done)->count(),
                'overdue_tasks' => $activeTasks->filter(fn ($task): bool => $task->due_date?->lt($now->toDateString()) === true)->count(),
                'blocked_tasks' => $tasks->where('status', TaskStatus::Blocked)->count(),
            ],
            'statusCounts' => $statusCounts,
            'projects' => $projects->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'identifier' => $project->identifier,
                'tasks' => $project->tasks_count,
                'done_tasks' => $project->tasks->where('status', TaskStatus::Done)->count(),
                'active_tasks' => $project->tasks->reject(fn ($task): bool => in_array($task->status->value, [TaskStatus::Done->value, TaskStatus::Canceled->value], true))->count(),
            ])->values(),
            'activities' => $activities->map(fn (ActivityLog $activity): array => [
                'id' => $activity->id,
                'action' => $activity->action,
                'description' => $activity->description,
                'created_at' => $activity->created_at->toIso8601String(),
                'actor' => $activity->actor?->only(['id', 'name']),
            ])->values(),
        ]);
    }
}
