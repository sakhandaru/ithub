<?php

namespace App\Actions\Workspaces;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\SystemRole;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddWorkspaceMember
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Workspace $workspace, User $member, SystemRole $role): WorkspaceMembership
    {
        return DB::transaction(function () use ($actor, $workspace, $member, $role): WorkspaceMembership {
            $legacyOwner = $workspace->memberships()
                ->whereBelongsTo($actor)
                ->where('role', WorkspaceRole::Owner->value)
                ->exists();

            if (! $actor->isAdmin() && ! $legacyOwner) {
                throw ValidationException::withMessages(['role' => 'Hanya Admin yang dapat mengelola role login.']);
            }

            $membership = $workspace->memberships()->create([
                'user_id' => $member->getKey(),
                'role' => $role === SystemRole::Admin ? WorkspaceRole::Owner : WorkspaceRole::Member,
                'invited_by_id' => $actor->getKey(),
                'joined_at' => now(),
            ]);
            $member->update(['system_role' => $role]);

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
