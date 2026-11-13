<?php

namespace App\Actions\Units;

use App\Actions\Audit\RecordAuditLog;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class CreateUnit
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Workspace $workspace, string $name): Unit
    {
        return DB::transaction(function () use ($actor, $workspace, $name): Unit {
            $unit = $workspace->units()->create([
                'name' => $name,
                'created_by_id' => $actor->getKey(),
            ]);

            $this->recordAuditLog->handle(
                auditable: $unit,
                action: 'unit.created',
                actor: $actor,
                workspace: $workspace,
                after: ['name' => $unit->name],
            );

            return $unit;
        });
    }
}
