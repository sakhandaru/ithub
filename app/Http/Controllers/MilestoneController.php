<?php

namespace App\Http\Controllers;

use App\Actions\Milestones\CreateMilestone;
use App\Actions\Milestones\DeleteMilestone;
use App\Http\Requests\StoreMilestoneRequest;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MilestoneController extends Controller
{
    public function store(StoreMilestoneRequest $request, Workspace $workspace, Project $project, CreateMilestone $createMilestone): RedirectResponse
    {
        $createMilestone->handle($request->user(), $project, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone dibuat.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, Milestone $milestone, DeleteMilestone $deleteMilestone): RedirectResponse
    {
        Gate::authorize('delete', $milestone);
        $deleteMilestone->handle(request()->user(), $milestone);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone dihapus.']);

        return back();
    }
}
