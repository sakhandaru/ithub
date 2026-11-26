<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectPlanningController extends Controller
{
    public function inbox(Workspace $workspace, Project $project): RedirectResponse
    {
        Gate::authorize('view', $project);

        return to_route('workspaces.projects.tasks.index', [
            'workspace' => $workspace,
            'project' => $project,
            'view' => 'list',
            'status' => TaskStatus::Backlog->value,
        ]);
    }

    public function releases(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/Releases', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $this->projectSummary($project),
        ]);
    }

    public function updates(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/Updates', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $this->projectSummary($project),
        ]);
    }

    /** @return array{id: string, name: string, identifier: string, archived: bool} */
    private function projectSummary(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'identifier' => $project->identifier,
            'archived' => $project->isArchived(),
        ];
    }
}
