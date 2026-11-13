<?php

namespace App\Actions\Milestones;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteMilestone
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    public function handle(User $actor, Milestone $milestone): void
    {
        DB::transaction(function () use ($actor, $milestone): void {
            $project = $milestone->project;
            $this->recordAuditLog->handle(auditable: $project, action: 'milestone.deleted', actor: $actor, workspace: $project->workspace, before: $milestone->only(['id', 'name', 'target_date']));
            $milestone->delete();
            $this->recordActivity->handle($project, 'milestone.deleted', $actor, $project->workspace, 'Milestone dihapus.', ['name' => $milestone->name]);
        });
    }
}
