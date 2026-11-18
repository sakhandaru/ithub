<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\AddWorkspaceMember;
use App\Actions\Workspaces\RemoveWorkspaceMember;
use App\Actions\Workspaces\UpdateWorkspaceMemberRole;
use App\Enums\SystemRole;
use App\Enums\WorkspaceRole;
use App\Http\Requests\StoreWorkspaceMemberRequest;
use App\Http\Requests\UpdateWorkspaceMemberRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WorkspaceMemberController extends Controller
{
    public function store(StoreWorkspaceMemberRequest $request, Workspace $workspace, AddWorkspaceMember $addMember): RedirectResponse
    {
        $member = User::query()->where('email', $request->string('email')->toString())->firstOrFail();
        $addMember->handle(
            actor: $request->user(),
            workspace: $workspace,
            member: $member,
            role: $this->systemRoleFromInput($request->string('role')->toString()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anggota ditambahkan.']);

        return back();
    }

    public function update(
        UpdateWorkspaceMemberRequest $request,
        Workspace $workspace,
        WorkspaceMembership $membership,
        UpdateWorkspaceMemberRole $updateRole,
    ): RedirectResponse {
        $updateRole->handle(
            actor: $request->user(),
            membership: $membership,
            role: $this->systemRoleFromInput($request->string('role')->toString()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role login anggota diperbarui.']);

        return back();
    }

    public function destroy(
        Workspace $workspace,
        WorkspaceMembership $membership,
        RemoveWorkspaceMember $removeMember,
    ): RedirectResponse {
        Gate::authorize('delete', $membership);
        $removeMember->handle(request()->user(), $membership);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anggota dihapus.']);

        return back();
    }

    private function systemRoleFromInput(string $role): SystemRole
    {
        return SystemRole::tryFrom($role) ?? match (WorkspaceRole::from($role)) {
            WorkspaceRole::Owner, WorkspaceRole::WorkspaceAdmin => SystemRole::Admin,
            WorkspaceRole::Member => SystemRole::User,
            WorkspaceRole::Guest => SystemRole::Auditor,
        };
    }
}
