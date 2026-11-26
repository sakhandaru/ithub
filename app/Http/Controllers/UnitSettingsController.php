<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UnitSettingsController extends Controller
{
    public function __invoke(Request $request, Unit $unit): Response
    {
        Gate::authorize('view', $unit);
        $unit->load(['memberships.user:id,name,email,avatar'])->loadCount(['projects']);

        return Inertia::render('units/Settings', [
            'unit' => [
                'id' => $unit->id,
                'name' => $unit->name,
                'workspace_id' => $unit->workspace_id,
                'projects_count' => $unit->projects_count,
            ],
            'members' => $unit->memberships->map(fn ($membership): array => [
                'id' => $membership->id,
                'user' => $membership->user->only(['id', 'name', 'email', 'avatar']),
            ])->values(),
            'canManage' => Gate::allows('update', $unit),
        ]);
    }
}
