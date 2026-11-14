<?php

namespace App\Actions\SavedViews;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Project;
use App\Models\SavedView;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateSavedView
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $actor, Project $project, array $attributes): SavedView
    {
        return DB::transaction(function () use ($actor, $project, $attributes): SavedView {
            $savedView = $project->savedViews()->create([
                ...$attributes,
                'workspace_id' => $project->workspace_id,
                'user_id' => $actor->getKey(),
            ]);

            $this->recordAuditLog->handle(auditable: $savedView, action: 'saved_view.created', actor: $actor, workspace: $project->workspace, after: $savedView->only(['name', 'filters', 'grouping', 'compact']));
            $this->recordActivity->handle($project, 'saved_view.created', $actor, $project->workspace, 'Saved View dibuat.', ['name' => $savedView->name]);

            return $savedView;
        });
    }
}
