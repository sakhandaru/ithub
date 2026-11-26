<?php

namespace App\Actions\Workspaces;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\SystemRole;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateWorkspaceMemberRole
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, WorkspaceMembership $membership, SystemRole $role): WorkspaceMembership
    {
        if ($role === SystemRole::Admin && ! $actor->isAdmin()) {
            throw ValidationException::withMessages(['role' => 'Hanya Admin sistem yang dapat memberikan role Admin.']);
        }

        return DB::transaction(function () use ($actor, $membership, $role): WorkspaceMembership {
            $workspace = $membership->workspace;
            $membershipRole = WorkspaceRole::from((string) $membership->getRawOriginal('role'));

            $legacyOwner = $workspace->memberships()
                ->whereBelongsTo($actor)
                ->where('role', WorkspaceRole::Owner->value)
                ->exists();

            if (! $actor->isAdmin() && ! $legacyOwner) {
                throw ValidationException::withMessages(['role' => 'Hanya Admin yang dapat mengelola role login.']);
            }

            if ($membership->user->isAdmin() && $role !== SystemRole::Admin && User::query()->where('system_role', SystemRole::Admin->value)->count() === 1) {
                throw ValidationException::withMessages(['role' => 'Sistem harus memiliki minimal satu Admin.']);
            }

            $before = ['role' => $membershipRole->value];
            $membership->update(['role' => $role === SystemRole::Admin ? WorkspaceRole::Owner : WorkspaceRole::Member]);
            $membership->user->update(['system_role' => $role]);

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
