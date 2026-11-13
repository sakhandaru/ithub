<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\AddTaskRelation;
use App\Actions\Tasks\DeleteTaskRelation;
use App\Http\Requests\StoreTaskRelationRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskRelation;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TaskRelationController extends Controller
{
    public function store(StoreTaskRelationRequest $request, Workspace $workspace, Project $project, Task $task, AddTaskRelation $addTaskRelation): RedirectResponse
    {
        $addTaskRelation->handle($request->user(), $task, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Relasi Task ditambahkan.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, Task $task, TaskRelation $relation, DeleteTaskRelation $deleteTaskRelation): RedirectResponse
    {
        Gate::authorize('delete', $relation);
        $deleteTaskRelation->handle(request()->user(), $relation);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Relasi Task dihapus.']);

        return back();
    }
}
