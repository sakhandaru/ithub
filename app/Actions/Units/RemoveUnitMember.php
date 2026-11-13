<?php

namespace App\Actions\Units;

use App\Actions\Audit\RecordAuditLog;
use App\Models\UnitMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RemoveUnitMember
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, UnitMembership $membership): void
    {
        DB::transaction(function () use ($actor, $membership): void {
            $unit = $membership->unit;
            $before = ['unit_id' => $unit->getKey(), 'user_id' => $membership->user_id];
            $membership->delete();

            $this->recordAuditLog->handle(
                auditable: $membership,
                action: 'unit.member.removed',
                actor: $actor,
                workspace: $unit->workspace,
                before: $before,
            );
        });
    }
}
