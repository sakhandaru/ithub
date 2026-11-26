<?php

namespace App\Http\Controllers;

use App\Actions\Modules\CreateModule;
use App\Actions\Modules\DeleteModule;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreModuleRequest;
use App\Models\Module;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ModuleController extends Controller
{
    public function index(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/Modules', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']) + ['archived' => $project->isArchived()],
            'modules' => $project->modules()->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', TaskStatus::Done->value)])->orderBy('name')->get(['id', 'name', 'description'])->map(function (Module $module): array {
                $statusCounts = $module->tasks()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'description' => $module->description,
                    'tasks_count' => $module->tasks_count,
                    'completed_tasks_count' => $module->completed_tasks_count,
                    'status_counts' => collect(TaskStatus::cases())->mapWithKeys(fn (TaskStatus $status): array => [$status->value => (int) ($statusCounts[$status->value] ?? 0)])->all(),
                ];
            })->values(),
            'releases' => $project->releases()->orderByDesc('target_date')->orderBy('name')->get(['id', 'name']),
            'canManage' => Gate::allows('update', $project),
        ]);
    }

    public function store(StoreModuleRequest $request, Workspace $workspace, Project $project, CreateModule $createModule): RedirectResponse
    {
        $createModule->handle($request->user(), $project, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modul dibuat.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, Module $module, DeleteModule $deleteModule): RedirectResponse
    {
        Gate::authorize('delete', $module);
        $deleteModule->handle(request()->user(), $module);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modul dihapus.']);

        return back();
    }
}
