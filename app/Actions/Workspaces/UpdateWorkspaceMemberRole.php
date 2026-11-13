<?php

namespace App\Actions\Workspaces;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateWorkspaceMemberRole
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, WorkspaceMembership $membership, WorkspaceRole $role): WorkspaceMembership
    {
        return DB::transaction(function () use ($actor, $membership, $role): WorkspaceMembership {
            $workspace = $membership->workspace;
            $actorRole = WorkspaceRole::from((string) $workspace->memberships()->whereBelongsTo($actor)->firstOrFail()->getRawOriginal('role'));
            $membershipRole = WorkspaceRole::from((string) $membership->getRawOriginal('role'));

            if ($actorRole !== WorkspaceRole::Owner && ($membershipRole === WorkspaceRole::Owner || $role === WorkspaceRole::Owner)) {
                throw ValidationException::withMessages(['role' => 'Hanya Owner yang dapat mengubah role Owner.']);
            }

            if ($membershipRole === WorkspaceRole::Owner && $role !== WorkspaceRole::Owner && $workspace->memberships()->where('role', WorkspaceRole::Owner->value)->count() === 1) {
                throw ValidationException::withMessages(['role' => 'Workspace harus memiliki minimal satu Owner.']);
            }

            $before = ['role' => $membershipRole->value];
            $membership->update(['role' => $role]);

            $this->recordAuditLog->handle(
                auditable: $membership,
                action: 'workspace.member.role_updated',
                actor: $actor,
                workspace: $workspace,
                before: $before,
                after: ['role' => $role->value],
            );

            return $membership;
        });
    }
}
