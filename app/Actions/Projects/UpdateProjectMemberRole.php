<?php

namespace App\Actions\Projects;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\ProjectRole;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateProjectMemberRole
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, ProjectMembership $membership, ProjectRole $role): ProjectMembership
    {
        return DB::transaction(function () use ($actor, $membership, $role): ProjectMembership {
            $project = $membership->project;
            $membershipRole = ProjectRole::from((string) $membership->getRawOriginal('role'));
            $actorMembership = $project->memberships()->whereBelongsTo($actor)->first();
            $actorRole = $actorMembership ? ProjectRole::from((string) $actorMembership->getRawOriginal('role')) : null;
            $workspaceRole = $project->workspace->memberships()->whereBelongsTo($actor)->first();
            $isWorkspaceAdmin = $workspaceRole && in_array((string) $workspaceRole->getRawOriginal('role'), ['owner', 'workspace_admin'], true);

            if ($actorRole !== ProjectRole::ProjectAdmin && ! $isWorkspaceAdmin) {
                throw ValidationException::withMessages(['role' => 'Anda tidak dapat mengubah role Project ini.']);
            }

            if ($membershipRole === ProjectRole::ProjectAdmin && $role !== ProjectRole::ProjectAdmin && $project->memberships()->where('role', ProjectRole::ProjectAdmin->value)->count() === 1) {
                throw ValidationException::withMessages(['role' => 'Project harus memiliki minimal satu Project Admin.']);
            }

            $membership->update(['role' => $role]);
            $this->recordAuditLog->handle(
                auditable: $membership,
                action: 'project.member.role_updated',
                actor: $actor,
                workspace: $project->workspace,
                before: ['role' => $membershipRole->value],
                after: ['role' => $role->value],
            );

            return $membership;
        });
    }
}
