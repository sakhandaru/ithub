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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceSettingsController extends Controller
{
    public function show(Request $request, Workspace $workspace): Response
    {
        Gate::authorize('update', $workspace);

        $section = $request->string('section', 'general')->toString();
        $section = in_array($section, ['general', 'members', 'units', 'access'], true) ? $section : 'general';

        $memberships = collect();
        $units = collect();

        if (in_array($section, ['members', 'units'], true)) {
            $workspace->load([
                'memberships' => fn ($query) => $query->with('user:id,name,email,avatar,system_role')->orderBy('joined_at'),
            ]);
            $memberships = $workspace->memberships;
        }

        if ($section === 'units') {
            $workspace->load([
                'units' => fn ($query) => $query->with(['memberships.user:id,name,email,avatar'])->orderBy('name'),
            ]);
            $units = $workspace->units;
        }

        return Inertia::render('workspaces/Settings', [
            'workspace' => $workspace->only(['id', 'name', 'timezone']),
            'section' => $section,
            'memberships' => $memberships->map(fn (WorkspaceMembership $membership): array => [
                'id' => $membership->id,
                'user' => $membership->user->only(['id', 'name', 'email', 'avatar']),
                'role' => ($membership->user->system_role ?? SystemRole::User)->value,
                'role_label' => ($membership->user->system_role ?? SystemRole::User)->label(),
                'joined_at' => CarbonImmutable::parse((string) $membership->getRawOriginal('joined_at'))->toIso8601String(),
            ])->values(),
            'units' => $units->map(fn (Unit $unit): array => [
                'id' => $unit->id,
                'name' => $unit->name,
                'memberships' => $unit->memberships->map(fn (UnitMembership $membership): array => [
                    'id' => $membership->id,
                    'user' => $membership->user->only(['id', 'name', 'email', 'avatar']),
                ])->values(),
            ])->values(),
            'roles' => collect(SystemRole::cases())->map(fn (SystemRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
            'timezones' => $section === 'general' ? config('sitop.workspace_timezones') : [],
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
