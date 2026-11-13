<?php

namespace App\Actions\Projects;

use App\Actions\Audit\RecordAuditLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RestoreProject
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Project $project): Project
    {
        return DB::transaction(function () use ($actor, $project): Project {
            $project->update(['archived_at' => null, 'archived_by_id' => null]);
            $this->recordAuditLog->handle(
                auditable: $project,
                action: 'project.restored',
                actor: $actor,
                workspace: $project->workspace,
            );

            return $project;
        });
    }
}
