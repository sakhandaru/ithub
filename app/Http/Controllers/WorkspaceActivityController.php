<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceActivityController extends Controller
{
    public function __invoke(Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);

        return Inertia::render('workspace/Activity', [
            'workspace' => $workspace->only(['id', 'name', 'timezone']),
            'activities' => ActivityLog::query()
                ->whereBelongsTo($workspace)
                ->with(['actor:id,name', 'subject'])
                ->latest('created_at')
                ->get()
                ->map(fn (ActivityLog $activity): array => [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'created_at' => $activity->created_at->toIso8601String(),
                    'actor' => $activity->actor?->only(['id', 'name']),
                    'entity' => $activity->entityData($workspace),
                    'ip_address' => $activity->ip_address,
                ])
                ->values(),
        ]);
    }
}
