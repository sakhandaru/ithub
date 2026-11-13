<?php

namespace App\Actions\Milestones;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateMilestone
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $actor, Project $project, array $attributes): Milestone
    {
        return DB::transaction(function () use ($actor, $project, $attributes): Milestone {
            $milestone = $project->milestones()->create($attributes);
            $this->recordAuditLog->handle(auditable: $milestone, action: 'milestone.created', actor: $actor, workspace: $project->workspace, after: $milestone->only(['name', 'target_date']));
            $this->recordActivity->handle($project, 'milestone.created', $actor, $project->workspace, 'Milestone dibuat.', ['name' => $milestone->name]);

            return $milestone;
        });
    }
}
