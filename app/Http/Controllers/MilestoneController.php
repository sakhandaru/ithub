<?php

namespace App\Http\Controllers;

use App\Actions\Milestones\CreateMilestone;
use App\Actions\Milestones\DeleteMilestone;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreMilestoneRequest;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MilestoneController extends Controller
{
    public function index(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/Milestones', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'archived_at']) + [
                'archived' => $project->isArchived(),
            ],
            'milestones' => $project->milestones()
                ->withCount([
                    'tasks',
                    'tasks as completed_tasks_count' => fn ($query) => $query->where('status', TaskStatus::Done->value),
                ])
                ->orderBy('target_date')
                ->orderBy('name')
                ->get(['id', 'name', 'target_date', 'release_id'])
                ->map(fn (Milestone $milestone): array => [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'release_id' => $milestone->release_id,
                    'target_date' => $milestone->target_date?->format('Y-m-d'),
                    'tasks_count' => $milestone->tasks_count,
                    'completed_tasks_count' => $milestone->completed_tasks_count,
                    'progress' => $milestone->tasks_count === 0 ? 0 : (int) round(($milestone->completed_tasks_count / $milestone->tasks_count) * 100),
                ])
                ->values(),
            'releases' => $project->releases()->orderByDesc('target_date')->orderBy('name')->get(['id', 'name']),
            'canManage' => Gate::allows('update', $project),
        ]);
    }

    public function store(StoreMilestoneRequest $request, Workspace $workspace, Project $project, CreateMilestone $createMilestone): RedirectResponse
    {
        $createMilestone->handle($request->user(), $project, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone dibuat.']);

        return back();
    }

    public function update(StoreMilestoneRequest $request, Workspace $workspace, Project $project, Milestone $milestone): RedirectResponse
    {
        Gate::authorize('update', $project);
        abort_unless($milestone->project_id === $project->id, 404);
        $milestone->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone diperbarui.']);

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
