<?php

namespace App\Actions\Units;

use App\Enums\WorkspaceRole;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateInitialUnit
{
    public function handle(User $actor, string $name): Unit
    {
        return DB::transaction(function () use ($actor, $name): Unit {
            $workspace = Workspace::query()->first() ?? Workspace::query()->create([
                'name' => 'ithub',
                'timezone' => 'Asia/Jakarta',
                'created_by_id' => $actor->getKey(),
            ]);

            $workspace->memberships()->firstOrCreate(
                ['user_id' => $actor->getKey()],
                ['role' => WorkspaceRole::Owner, 'invited_by_id' => $actor->getKey(), 'joined_at' => now()],
            );

            $unit = $workspace->units()->create(['name' => $name, 'created_by_id' => $actor->getKey()]);
            $unit->memberships()->create(['user_id' => $actor->getKey(), 'added_by_id' => $actor->getKey()]);

            $actor->forceFill([
                'active_workspace_id' => $workspace->getKey(),
                'active_unit_id' => $unit->getKey(),
            ])->save();

            return $unit;
        });
    }
}
