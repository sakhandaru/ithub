<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\SetActiveWorkspace;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SetActiveWorkspace $setActiveWorkspace): Response|RedirectResponse
    {
        $user = $request->user();
        $workspace = Workspace::query()
            ->visibleTo($user)
            ->whereKey($user->active_workspace_id)
            ->first() ?? Workspace::query()->visibleTo($user)->orderBy('name')->first();

        if (! $workspace) {
            return to_route('workspaces.create');
        }

        if ($user->active_workspace_id !== $workspace->getKey()) {
            $setActiveWorkspace->handle($user, $workspace);
        }

        $projects = Project::query()->whereBelongsTo($workspace)->visibleTo($user)->whereNull('archived_at')->withCount('tasks')->with(['unit:id,name'])->get();
        $tasks = Project::query()->whereBelongsTo($workspace)->visibleTo($user)->whereNull('archived_at')->with('tasks')->get()->flatMap->tasks;
        $now = CarbonImmutable::now($workspace->timezone);
        $active = $tasks->filter(fn ($task) => ! in_array((string) $task->getRawOriginal('status'), [TaskStatus::Done->value, TaskStatus::Canceled->value], true));
        $myTasks = $active->where('assignee_id', $user->getKey());
        $overdue = $active->filter(fn ($task) => $task->due_date && $task->due_date->lt($now->toDateString()));
        $blocked = $active->filter(fn ($task) => $task->getRawOriginal('status') === TaskStatus::Blocked->value);
        $focus = $request->string('focus')->toString();
        $focus = in_array($focus, ['my_tasks', 'overdue', 'blocked'], true) ? $focus : null;
        $focusTasks = match ($focus) {
            'my_tasks' => $myTasks,
            'overdue' => $overdue,
            'blocked' => $blocked,
            default => collect(),
        };

        return Inertia::render('Dashboard', [
            'workspace' => $workspace->only(['id', 'name', 'timezone']),
            'stats' => ['my_tasks' => $myTasks->count(), 'overdue' => $overdue->count(), 'blocked' => $blocked->count(), 'active' => $active->count()],
            'projects' => $projects->map(fn (Project $project): array => ['id' => $project->id, 'name' => $project->name, 'identifier' => $project->identifier, 'unit' => $project->unit?->name, 'tasks' => $project->tasks_count])->values(),
            'myTasks' => $myTasks->sortBy('due_date')->take(8)->map(fn ($task): array => ['id' => $task->id, 'identifier' => $task->identifier, 'title' => $task->title, 'project_id' => $task->project_id, 'status' => $task->status->value, 'due_date' => $task->due_date?->toDateString()])->values(),
            'overdueTasks' => $overdue->take(8)->map(fn ($task): array => ['id' => $task->id, 'identifier' => $task->identifier, 'title' => $task->title, 'project_id' => $task->project_id, 'due_date' => $task->due_date?->toDateString()])->values(),
            'blockedTasks' => $blocked->take(8)->map(fn ($task): array => ['id' => $task->id, 'identifier' => $task->identifier, 'title' => $task->title, 'project_id' => $task->project_id])->values(),
            'focus' => $focus,
            'focusTasks' => $focusTasks->take(30)->map(fn ($task): array => ['id' => $task->id, 'identifier' => $task->identifier, 'title' => $task->title, 'project_id' => $task->project_id, 'status' => $task->status->value, 'due_date' => $task->due_date?->toDateString()])->values(),
        ]);
    }
}
