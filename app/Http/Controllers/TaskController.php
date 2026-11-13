<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\AssignTask;
use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\UpdateTask;
use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TaskController extends Controller
{
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

    public function assign(AssignTaskRequest $request, Workspace $workspace, Project $project, Task $task, AssignTask $assignTask): RedirectResponse
    {
        $assignee = $request->filled('assignee_id')
            ? User::query()->findOrFail($request->integer('assignee_id'))
            : null;
        $assignTask->handle($request->user(), $task, $assignee);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'PIC Task berhasil diperbarui.']);

        return back();
    }
}
