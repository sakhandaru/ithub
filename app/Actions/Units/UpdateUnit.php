<?php

namespace App\Actions\Units;

use App\Actions\Audit\RecordAuditLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateUnit
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Unit $unit, string $name): Unit
    {
        return DB::transaction(function () use ($actor, $unit, $name): Unit {
            $before = ['name' => $unit->name];
            $unit->update(['name' => $name]);

            $this->recordAuditLog->handle(
                auditable: $unit,
                action: 'unit.updated',
                actor: $actor,
                workspace: $unit->workspace,
                before: $before,
                after: ['name' => $unit->name],
            );

            return $unit;
        });
    }
}
