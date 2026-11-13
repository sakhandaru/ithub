<?php

namespace App\Actions\Units;

use App\Actions\Audit\RecordAuditLog;
use App\Models\Unit;
use App\Models\UnitMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddUnitMember
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Unit $unit, User $member): UnitMembership
    {
        return DB::transaction(function () use ($actor, $unit, $member): UnitMembership {
            if (! $unit->workspace->memberships()->whereBelongsTo($member)->exists()) {
                throw ValidationException::withMessages(['user_id' => 'User harus menjadi anggota Workspace terlebih dahulu.']);
            }

            $membership = $unit->memberships()->create([
                'user_id' => $member->getKey(),
                'added_by_id' => $actor->getKey(),
            ]);

            $this->recordAuditLog->handle(
                auditable: $membership,
                action: 'unit.member.added',
                actor: $actor,
                workspace: $unit->workspace,
                after: ['unit_id' => $unit->getKey(), 'user_id' => $member->getKey()],
            );

            return $membership;
        });
    }
}
