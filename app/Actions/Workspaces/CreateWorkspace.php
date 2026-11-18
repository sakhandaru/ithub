<?php

namespace App\Actions\Workspaces;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class CreateWorkspace
{
    private const array InitialUnits = ['Development', 'Infra', 'Operasional'];

    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $creator, string $name, string $timezone): Workspace
    {
        return DB::transaction(function () use ($creator, $name, $timezone): Workspace {
            $workspace = Workspace::create([
                'name' => $name,
                'timezone' => $timezone,
                'created_by_id' => $creator->getKey(),
            ]);

            $workspace->memberships()->create([
                'user_id' => $creator->getKey(),
                'role' => WorkspaceRole::Owner,
                'invited_by_id' => $creator->getKey(),
                'joined_at' => now(),
            ]);

            foreach (self::InitialUnits as $unitName) {
                $unit = $workspace->units()->create([
                    'name' => $unitName,
                    'created_by_id' => $creator->getKey(),
                ]);

                $unit->memberships()->create([
                    'user_id' => $creator->getKey(),
                    'added_by_id' => $creator->getKey(),
                ]);
            }

            $creator->forceFill([
                'active_workspace_id' => $workspace->getKey(),
                'active_unit_id' => $workspace->units()->oldest('created_at')->value('id'),
            ])->save();

            $this->recordAuditLog->handle(
                auditable: $workspace,
                action: 'workspace.created',
                actor: $creator,
                workspace: $workspace,
                after: ['name' => $workspace->name, 'timezone' => $workspace->timezone],
            );

            return $workspace->load('units');
        });
    }
}
