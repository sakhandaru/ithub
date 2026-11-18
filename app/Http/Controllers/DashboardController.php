<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\SetActiveWorkspace;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\Unit;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SetActiveWorkspace $setActiveWorkspace): Response|RedirectResponse
    {
        $user = $request->user();
        if (! $user->unitMemberships()->exists()) {
            return to_route('units.create');
        }

        $units = $user->unitMemberships()->with('unit.workspace')->get()->pluck('unit')->filter();
        $workspace = $units->first()?->workspace ?? Workspace::query()
            ->visibleTo($user)
            ->whereKey($user->active_workspace_id)
            ->first() ?? Workspace::query()->visibleTo($user)->orderBy('name')->first();

        if (! $workspace) {
            return to_route('workspaces.create');
        }

        if ($user->active_workspace_id !== $workspace->getKey()) {
            $setActiveWorkspace->handle($user, $workspace);
        }

        return $this->renderDashboard($request, $units, $workspace, 'global', route('dashboard'));
    }

    public function unit(Request $request, Unit $unit): Response
    {
        Gate::authorize('view', $unit);

        return $this->renderDashboard($request, collect([$unit->load('workspace')]), $unit->workspace, 'unit', route('units.dashboard', $unit));
    }

    /**
     * @param  Collection<int, Unit>  $units
     */
    private function renderDashboard(Request $request, Collection $units, Workspace $workspace, string $scope, string $dashboardUrl): Response
    {
        $user = $request->user();
        $projects = Project::query()
            ->whereIn('unit_id', $units->pluck('id'))
            ->visibleTo($user)
            ->whereNull('archived_at')
            ->withCount('tasks')
            ->with(['unit:id,name', 'workspace:id,name'])
            ->get();
        $tasks = Task::query()->whereIn('project_id', $projects->pluck('id'))->with('project')->get();
        $tasksByProject = $tasks->groupBy('project_id');
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
        $activities = ActivityLog::query()
            ->whereIn('workspace_id', $units->pluck('workspace_id'))
            ->with('actor:id,name')
            ->latest('created_at')
            ->limit(8)
            ->get();

        return Inertia::render('Dashboard', [
            'scope' => $scope,
            'dashboardUrl' => $dashboardUrl,
            'workspace' => $workspace->only(['id', 'name', 'timezone']),
            'unit' => $scope === 'unit' ? $units->first()?->only(['id', 'name']) : null,
            'stats' => ['my_tasks' => $myTasks->count(), 'overdue' => $overdue->count(), 'blocked' => $blocked->count(), 'active' => $active->count()],
            'projects' => $projects->map(function (Project $project) use ($tasksByProject, $now): array {
                $projectTasks = $tasksByProject->get($project->id, collect());
                $activeTasks = $projectTasks->filter(fn ($task): bool => ! in_array((string) $task->getRawOriginal('status'), [TaskStatus::Done->value, TaskStatus::Canceled->value], true));
                $overdueTasks = $activeTasks->filter(fn ($task): bool => $task->due_date && $task->due_date->lt($now->toDateString()));
                $blockedTasks = $activeTasks->filter(fn ($task): bool => $task->getRawOriginal('status') === TaskStatus::Blocked->value);

                return [
                    'id' => $project->id,
                    'workspace_id' => $project->workspace_id,
                    'name' => $project->name,
                    'identifier' => $project->identifier,
                    'unit' => $project->unit?->name,
                    'tasks' => $project->tasks_count,
                    'active_tasks' => $activeTasks->count(),
                    'overdue_tasks' => $overdueTasks->count(),
                    'blocked_tasks' => $blockedTasks->count(),
                    'attention' => $overdueTasks->count() + ($blockedTasks->count() * 2),
                ];
            })->sortByDesc('attention')->values(),
            'myTasks' => $myTasks->sortBy('due_date')->take(8)->map(fn ($task): array => ['id' => $task->id, 'identifier' => $task->identifier, 'title' => $task->title, 'workspace_id' => $task->project->workspace_id, 'project_id' => $task->project_id, 'status' => $task->status->value, 'due_date' => $task->due_date?->toDateString()])->values(),
            'overdueTasks' => $overdue->take(8)->map(fn ($task): array => ['id' => $task->id, 'identifier' => $task->identifier, 'title' => $task->title, 'workspace_id' => $task->project->workspace_id, 'project_id' => $task->project_id, 'due_date' => $task->due_date?->toDateString()])->values(),
            'blockedTasks' => $blocked->take(8)->map(fn ($task): array => ['id' => $task->id, 'identifier' => $task->identifier, 'title' => $task->title, 'workspace_id' => $task->project->workspace_id, 'project_id' => $task->project_id])->values(),
            'focus' => $focus,
            'focusTasks' => $focusTasks->take(30)->map(fn ($task): array => ['id' => $task->id, 'identifier' => $task->identifier, 'title' => $task->title, 'workspace_id' => $task->project->workspace_id, 'project_id' => $task->project_id, 'status' => $task->status->value, 'due_date' => $task->due_date?->toDateString()])->values(),
            'activities' => $activities->map(fn (ActivityLog $activity): array => ['id' => $activity->id, 'action' => $activity->action, 'description' => $activity->description, 'created_at' => $activity->created_at->toIso8601String(), 'actor' => $activity->actor?->only(['id', 'name'])])->values(),
        ]);
    }
}
