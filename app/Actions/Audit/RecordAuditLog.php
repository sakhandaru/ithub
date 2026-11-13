<?php

namespace App\Actions\Audit;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use App\Support\RequestContext;
use Illuminate\Database\Eloquent\Model;

final class RecordAuditLog
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function handle(
        Model $auditable,
        string $action,
        ?User $actor,
        ?Workspace $workspace,
        ?array $before = null,
        ?array $after = null,
    ): AuditLog {
        return AuditLog::create([
            'workspace_id' => $workspace?->getKey(),
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => (string) $auditable->getKey(),
            'request_id' => RequestContext::requestId(),
            'before' => $before,
            'after' => $after,
        ]);
    }
}
