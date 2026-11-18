<?php

namespace App\Http\Controllers;

use App\Actions\Projects\UpdateProject;
use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectSettingsController extends Controller
{
    public function show(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);
        $project->load(['unit', 'memberships.user']);
        $auditLogs = Gate::allows('update', $project)
            ? $project->auditLogs()->with('actor')->latest('created_at')->get()
            : collect();

        return Inertia::render('projects/Settings', [
            'workspace' => $workspace->only(['id', 'name']),
            'project' => $project->only(['id', 'name', 'identifier', 'description', 'unit_id', 'project_lead_id', 'default_assignee_id', 'start_date', 'target_date', 'archived_at']) + [
                'visibility' => ProjectVisibility::from((string) $project->getRawOriginal('visibility'))->value,
                'status' => ProjectStatus::from((string) $project->getRawOriginal('status'))->value,
                'archived' => $project->isArchived(),
            ],
            'units' => $workspace->units()->orderBy('name')->get(['id', 'name']),
            'members' => $workspace->memberships()->with('user')->orderBy('joined_at')->get()->map(fn ($membership): array => [
                'id' => $membership->user->id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
            ])->values(),
            'projectMembers' => $project->memberships->map(fn (ProjectMembership $membership): array => [
                'id' => $membership->id,
                'user' => $membership->user->only(['id', 'name', 'email']),
                'role' => ProjectRole::from((string) $membership->getRawOriginal('role'))->value,
                'role_label' => ProjectRole::from((string) $membership->getRawOriginal('role'))->label(),
                'joined_at' => CarbonImmutable::parse((string) $membership->getRawOriginal('joined_at'))->toIso8601String(),
            ])->values(),
            'roles' => collect(ProjectRole::cases())->map(fn (ProjectRole $role): array => ['value' => $role->value, 'label' => $role->label()]),
            'statuses' => collect(ProjectStatus::cases())->map(fn (ProjectStatus $status): array => ['value' => $status->value, 'label' => $status->label()]),
            'visibilities' => collect(ProjectVisibility::cases())->map(fn (ProjectVisibility $visibility): array => ['value' => $visibility->value, 'label' => $visibility->label()]),
            'canManage' => Gate::allows('update', $project),
            'canRestore' => Gate::allows('restore', $project),
            'auditLogs' => $auditLogs->map(fn ($auditLog): array => [
                'id' => $auditLog->id,
                'action' => $auditLog->action,
                'created_at' => $auditLog->created_at->toIso8601String(),
                'actor' => $auditLog->actor?->only(['id', 'name']),
            ])->values(),
            'canViewAudit' => Gate::allows('update', $project),
        ]);
    }

    public function update(UpdateProjectRequest $request, Workspace $workspace, Project $project, UpdateProject $updateProject): RedirectResponse
    {
        $data = $request->validated();
        $updateProject->handle($request->user(), $project, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengaturan Project disimpan.']);

        return back();
    }
}
