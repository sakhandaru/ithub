<?php

namespace App\Actions\Workspaces;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\WorkspaceRole;
use App\Models\UnitMembership;
use App\Models\User;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RemoveWorkspaceMember
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, WorkspaceMembership $membership): void
    {
        DB::transaction(function () use ($actor, $membership): void {
            $workspace = $membership->workspace;
            $actorRole = WorkspaceRole::from((string) $workspace->memberships()->whereBelongsTo($actor)->firstOrFail()->getRawOriginal('role'));
            $membershipRole = WorkspaceRole::from((string) $membership->getRawOriginal('role'));

            if ($membershipRole === WorkspaceRole::Owner && $actorRole !== WorkspaceRole::Owner) {
                throw ValidationException::withMessages(['membership' => 'Hanya Owner yang dapat menghapus Owner lain.']);
            }

            if ($membershipRole === WorkspaceRole::Owner && $workspace->memberships()->where('role', WorkspaceRole::Owner->value)->count() === 1) {
                throw ValidationException::withMessages(['membership' => 'Owner terakhir tidak dapat dihapus.']);
            }

            $before = ['user_id' => $membership->user_id, 'role' => $membershipRole->value];

            UnitMembership::query()
                ->where('user_id', $membership->user_id)
                ->whereHas('unit', fn ($query) => $query->whereBelongsTo($workspace))
                ->delete();

            $removedUser = $membership->user;
            $membership->delete();

            if ($removedUser->active_workspace_id === $workspace->getKey()) {
                $nextWorkspaceId = $removedUser->workspaceMemberships()->value('workspace_id');
                $removedUser->forceFill(['active_workspace_id' => $nextWorkspaceId])->save();
            }

            $this->recordAuditLog->handle(
                auditable: $membership,
                action: 'workspace.member.removed',
                actor: $actor,
                workspace: $workspace,
                before: $before,
            );
        });
    }
}
