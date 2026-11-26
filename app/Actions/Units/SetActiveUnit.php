<?php

namespace App\Actions\Units;

use App\Models\Unit;
use App\Models\User;

class SetActiveUnit
{
    public function handle(User $user, Unit $unit): void
    {
        $user->forceFill([
            'active_unit_id' => $unit->getKey(),
            'active_workspace_id' => $unit->workspace_id,
        ])->save();
    }
}
