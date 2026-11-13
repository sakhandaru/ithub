<?php

namespace App\Actions\Workspaces;

use App\Actions\Audit\RecordAuditLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class UpdateWorkspace
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /** @param array{name: string, timezone: string} $attributes */
    public function handle(User $actor, Workspace $workspace, array $attributes): Workspace
    {
        return DB::transaction(function () use ($actor, $workspace, $attributes): Workspace {
            $before = $workspace->only(['name', 'timezone']);
            $workspace->update($attributes);

            $this->recordAuditLog->handle(
                auditable: $workspace,
                action: 'workspace.updated',
                actor: $actor,
                workspace: $workspace,
                before: $before,
                after: $workspace->only(['name', 'timezone']),
            );

            return $workspace;
        });
    }
}
