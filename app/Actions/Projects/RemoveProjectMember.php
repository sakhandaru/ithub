<?php

namespace App\Actions\Projects;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\ProjectRole;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RemoveProjectMember
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, ProjectMembership $membership): void
    {
        DB::transaction(function () use ($actor, $membership): void {
            $project = $membership->project;
            $membershipRole = ProjectRole::from((string) $membership->getRawOriginal('role'));
            $actorMembership = $project->memberships()->whereBelongsTo($actor)->first();
            $actorRole = $actorMembership ? ProjectRole::from((string) $actorMembership->getRawOriginal('role')) : null;
            $workspaceMembership = $project->workspace->memberships()->whereBelongsTo($actor)->first();
            $isWorkspaceAdmin = $workspaceMembership && in_array((string) $workspaceMembership->getRawOriginal('role'), ['owner', 'workspace_admin'], true);

            if ($actorRole !== ProjectRole::ProjectAdmin && ! $isWorkspaceAdmin) {
                throw ValidationException::withMessages(['membership' => 'Anda tidak dapat mengelola anggota Project ini.']);
            }

            if ($membershipRole === ProjectRole::ProjectAdmin && $project->memberships()->where('role', ProjectRole::ProjectAdmin->value)->count() === 1) {
                throw ValidationException::withMessages(['membership' => 'Project Admin terakhir tidak dapat dihapus.']);
            }

            $before = ['user_id' => $membership->user_id, 'role' => $membershipRole->value];
            $membership->delete();
            $this->recordAuditLog->handle(
                auditable: $membership,
                action: 'project.member.removed',
                actor: $actor,
                workspace: $project->workspace,
                before: $before,
            );
        });
    }
}
