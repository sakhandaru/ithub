<?php

namespace App\Actions\Projects;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateProject
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $actor, Project $project, array $attributes): Project
    {
        return DB::transaction(function () use ($actor, $project, $attributes): Project {
            $before = $project->only(['name', 'description', 'visibility', 'status', 'unit_id', 'project_lead_id', 'default_assignee_id', 'start_date', 'target_date']);
            $project->update($attributes);

            $this->recordAuditLog->handle(
                auditable: $project,
                action: 'project.updated',
                actor: $actor,
                workspace: $project->workspace,
                before: $before,
                after: $project->only(array_keys($before)),
            );
            $this->recordActivity->handle($project, 'project.updated', $actor, $project->workspace, 'Project diperbarui.', ['fields' => array_keys($attributes)]);

            return $project->refresh();
        });
    }
}
