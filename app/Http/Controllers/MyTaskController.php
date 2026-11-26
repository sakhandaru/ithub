<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MyTaskController extends Controller
{
    public function global(Request $request): Response
    {
        $user = $request->user();
        $unit = null;
        $unitId = $request->string('unit')->toString();

        if ($unitId !== '') {
            $unit = Unit::query()->findOrFail($unitId);
            Gate::authorize('view', $unit);
        }

        $unitIds = $unit ? collect([$unit->id]) : $user->unitMemberships()->pluck('unit_id');

        return $this->render($user, $unitIds, $unit);
    }

    public function unit(Unit $unit): RedirectResponse
    {
        Gate::authorize('view', $unit);

        return to_route('my-tasks', ['unit' => $unit->id]);
    }

    /**
     * @param  Collection<int, string>  $unitIds
     */
    private function render(User $user, Collection $unitIds, ?Unit $unit): Response
    {
        $projects = Project::query()
            ->visibleTo($user)
            ->whereIn('unit_id', $unitIds)
            ->whereNull('archived_at')
            ->with(['unit:id,name', 'workspace:id,name'])
            ->get(['id', 'workspace_id', 'unit_id', 'name', 'identifier']);

        $tasks = Task::query()
            ->where('assignee_id', $user->id)
            ->whereIn('project_id', $projects->pluck('id'))
            ->with(['project:id,workspace_id,unit_id,name,identifier', 'project.unit:id,name', 'labels:id,name,color'])
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();

        $now = CarbonImmutable::now();
        $activeTasks = $tasks->filter(fn (Task $task): bool => ! in_array((string) $task->getRawOriginal('status'), [TaskStatus::Done->value, TaskStatus::Canceled->value], true));

        return Inertia::render('MyTasks', [
            'scope' => $unit ? 'unit' : 'global',
            'unit' => $unit?->only(['id', 'name', 'workspace_id']),
            'units' => $user->units()->with('workspace:id,name')->orderBy('name')->get(['units.id', 'units.name', 'units.workspace_id'])->map(fn (Unit $availableUnit): array => [
                'id' => $availableUnit->id,
                'name' => $availableUnit->name,
                'workspace_id' => $availableUnit->workspace_id,
                'workspace_name' => $availableUnit->workspace?->name,
            ])->values(),
            'tasks' => $tasks->map(fn (Task $task): array => $this->taskSummary($task))->values(),
            'stats' => [
                'total' => $tasks->count(),
                'active' => $activeTasks->count(),
                'overdue' => $activeTasks->filter(fn (Task $task): bool => $task->due_date?->lt($now->toDateString()) ?? false)->count(),
                'completed' => $tasks->filter(fn (Task $task): bool => $task->getRawOriginal('status') === TaskStatus::Done->value)->count(),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function taskSummary(Task $task): array
    {
        $status = TaskStatus::from((string) $task->getRawOriginal('status'));
        $priority = $task->getRawOriginal('priority') ? TaskPriority::from((string) $task->getRawOriginal('priority')) : null;

        return [
            'id' => $task->id,
            'identifier' => $task->identifier,
            'title' => $task->title,
            'status' => $status->value,
            'status_label' => $status->label(),
            'priority_label' => $priority?->label(),
            'due_date' => $task->due_date?->toDateString(),
            'workspace_id' => $task->project->workspace_id,
            'project_id' => $task->project_id,
            'project_name' => $task->project->name,
            'project_identifier' => $task->project->identifier,
            'unit_name' => $task->project->unit?->name,
        ];
    }
}
