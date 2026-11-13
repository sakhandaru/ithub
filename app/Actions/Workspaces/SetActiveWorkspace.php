<?php

namespace App\Actions\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\AuthorizationException;

final class SetActiveWorkspace
{
    public function handle(User $user, Workspace $workspace): void
    {
        if (! $workspace->memberships()->whereBelongsTo($user)->exists()) {
            throw new AuthorizationException;
        }

        $user->forceFill(['active_workspace_id' => $workspace->getKey()])->save();
    }
}
