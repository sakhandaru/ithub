<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\SetActiveWorkspace;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SetActiveWorkspace $setActiveWorkspace): Response|RedirectResponse
    {
        $user = $request->user();
        $workspace = Workspace::query()
            ->visibleTo($user)
            ->whereKey($user->active_workspace_id)
            ->first() ?? Workspace::query()->visibleTo($user)->orderBy('name')->first();

        if (! $workspace) {
            return to_route('workspaces.create');
        }

        if ($user->active_workspace_id !== $workspace->getKey()) {
            $setActiveWorkspace->handle($user, $workspace);
        }

        return Inertia::render('Dashboard', [
            'workspace' => $workspace->only(['id', 'name', 'timezone']),
        ]);
    }
}
