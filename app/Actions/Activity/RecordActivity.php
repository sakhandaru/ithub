<?php

namespace App\Actions\Activity;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use App\Support\RequestContext;
use Illuminate\Database\Eloquent\Model;

final class RecordActivity
{
    /** @param array<string, mixed> $properties */
    public function handle(Model $subject, string $action, ?User $actor, Workspace $workspace, ?string $description = null, array $properties = []): ActivityLog
    {
        return ActivityLog::create([
            'workspace_id' => $workspace->getKey(),
            'actor_id' => $actor?->getKey(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => (string) $subject->getKey(),
            'action' => $action,
            'description' => $description,
            'properties' => [...$properties, 'request_id' => RequestContext::requestId()],
        ]);
    }
}
