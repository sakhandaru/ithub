<?php

namespace App\Actions\Workspaces;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddWorkspaceMember
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Workspace $workspace, User $member, WorkspaceRole $role): WorkspaceMembership
    {
        return DB::transaction(function () use ($actor, $workspace, $member, $role): WorkspaceMembership {
            $actorRole = WorkspaceRole::from((string) $workspace->memberships()->whereBelongsTo($actor)->firstOrFail()->getRawOriginal('role'));

            if ($role === WorkspaceRole::Owner && $actorRole !== WorkspaceRole::Owner) {
                throw ValidationException::withMessages(['role' => 'Hanya Owner yang dapat menambahkan Owner lain.']);
            }

            $membership = $workspace->memberships()->create([
                'user_id' => $member->getKey(),
                'role' => $role,
                'invited_by_id' => $actor->getKey(),
                'joined_at' => now(),
            ]);

            $this->recordAuditLog->handle(
                auditable: $membership,
                action: 'workspace.member.added',
                actor: $actor,
                workspace: $workspace,
                after: ['user_id' => $member->getKey(), 'role' => $role->value],
            );

            return $membership;
        });
    }
}
