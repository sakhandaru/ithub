<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\CreateWorkspace;
use App\Http\Requests\StoreWorkspaceRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('workspaces/Create', [
            'timezones' => config('sitop.workspace_timezones'),
        ]);
    }

    public function store(StoreWorkspaceRequest $request, CreateWorkspace $createWorkspace): RedirectResponse
    {
        $data = $request->validated();
        $createWorkspace->handle($request->user(), $data['name'], $data['timezone']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Workspace berhasil dibuat.']);

        return to_route('dashboard');
    }
}
