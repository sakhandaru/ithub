<?php

namespace App\Http\Controllers;

use App\Actions\Projects\AddProjectMember;
use App\Actions\Projects\RemoveProjectMember;
use App\Actions\Projects\UpdateProjectMemberRole;
use App\Enums\ProjectRole;
use App\Http\Requests\StoreProjectMemberRequest;
use App\Http\Requests\UpdateProjectMemberRequest;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProjectMemberController extends Controller
{
    public function store(StoreProjectMemberRequest $request, Workspace $workspace, Project $project, AddProjectMember $addMember): RedirectResponse
    {
        $member = User::query()->findOrFail($request->integer('user_id'));
        $addMember->handle($request->user(), $project, $member, ProjectRole::from($request->string('role')->toString()));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anggota ditambahkan ke Project.']);

        return back();
    }

    public function update(UpdateProjectMemberRequest $request, Workspace $workspace, Project $project, ProjectMembership $membership, UpdateProjectMemberRole $updateRole): RedirectResponse
    {
        $updateRole->handle($request->user(), $membership, ProjectRole::from($request->string('role')->toString()));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role Project diperbarui.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, ProjectMembership $membership, RemoveProjectMember $removeMember): RedirectResponse
    {
        Gate::authorize('delete', $membership);
        $removeMember->handle(request()->user(), $membership);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anggota dihapus dari Project.']);

        return back();
    }
}
