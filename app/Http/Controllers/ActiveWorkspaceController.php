<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\SetActiveWorkspace;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActiveWorkspaceController extends Controller
{
    public function __invoke(Request $request, Workspace $workspace, SetActiveWorkspace $setActiveWorkspace): RedirectResponse
    {
        Gate::authorize('view', $workspace);
        $setActiveWorkspace->handle($request->user(), $workspace);

        return to_route('dashboard');
    }
}
