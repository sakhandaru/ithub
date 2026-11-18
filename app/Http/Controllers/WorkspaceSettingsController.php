<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\UpdateWorkspace;
use App\Enums\SystemRole;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Unit;
use App\Models\UnitMembership;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceSettingsController extends Controller
{
    public function show(Workspace $workspace): Response
    {
        Gate::authorize('update', $workspace);

        $workspace->load([
            'memberships' => fn ($query) => $query->with('user')->orderBy('joined_at'),
            'units' => fn ($query) => $query->with(['memberships.user'])->orderBy('name'),
        ]);

        return Inertia::render('workspaces/Settings', [
            'workspace' => $workspace->only(['id', 'name', 'timezone']),
            'memberships' => $workspace->memberships->map(fn (WorkspaceMembership $membership): array => [
                'id' => $membership->id,
                'user' => $membership->user->only(['id', 'name', 'email']),
                'role' => ($membership->user->system_role ?? SystemRole::User)->value,
                'role_label' => ($membership->user->system_role ?? SystemRole::User)->label(),
                'joined_at' => CarbonImmutable::parse((string) $membership->getRawOriginal('joined_at'))->toIso8601String(),
            ])->values(),
            'units' => $workspace->units->map(fn (Unit $unit): array => [
                'id' => $unit->id,
                'name' => $unit->name,
                'memberships' => $unit->memberships->map(fn (UnitMembership $membership): array => [
                    'id' => $membership->id,
                    'user' => $membership->user->only(['id', 'name', 'email']),
                ])->values(),
            ])->values(),
            'roles' => collect(SystemRole::cases())->map(fn (SystemRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
            'timezones' => config('sitop.workspace_timezones'),
            'canAssignOwner' => false,
        ]);
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace, UpdateWorkspace $updateWorkspace): RedirectResponse
    {
        $data = $request->validated();
        $updateWorkspace->handle($request->user(), $workspace, [
            'name' => (string) $data['name'],
            'timezone' => (string) $data['timezone'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengaturan Workspace disimpan.']);

        return back();
    }
}
