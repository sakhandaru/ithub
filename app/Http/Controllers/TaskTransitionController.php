<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\TransitionTask;
use App\Enums\TaskStatus;
use App\Http\Requests\TransitionTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TaskTransitionController extends Controller
{
    public function __invoke(TransitionTaskRequest $request, Workspace $workspace, Project $project, Task $task, TransitionTask $transitionTask): RedirectResponse
    {
        $data = $request->validated();
        $transitionTask->handle(
            $request->user(),
            $task,
            TaskStatus::from($data['status']),
            $data['reason'] ?? null,
            $data['completion_note'] ?? null,
            $data['evidence_url'] ?? null,
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status Task berhasil diperbarui.']);

        return back();
    }
}
