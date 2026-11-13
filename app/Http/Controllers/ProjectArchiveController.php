<?php

namespace App\Http\Controllers;

use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\RestoreProject;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProjectArchiveController extends Controller
{
    public function archive(Request $request, Workspace $workspace, Project $project, ArchiveProject $archiveProject): RedirectResponse
    {
        Gate::authorize('archive', $project);
        $archiveProject->handle($request->user(), $project);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project diarsipkan.']);

        return to_route('workspaces.projects.show', [$workspace, $project]);
    }

    public function restore(Request $request, Workspace $workspace, Project $project, RestoreProject $restoreProject): RedirectResponse
    {
        Gate::authorize('restore', $project);
        $restoreProject->handle($request->user(), $project);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project dipulihkan.']);

        return to_route('workspaces.projects.show', [$workspace, $project]);
    }
}
