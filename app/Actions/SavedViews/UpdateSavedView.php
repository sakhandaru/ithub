<?php

namespace App\Actions\SavedViews;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\SavedView;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateSavedView
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $actor, SavedView $savedView, array $attributes): SavedView
    {
        return DB::transaction(function () use ($actor, $savedView, $attributes): SavedView {
            $project = $savedView->project;
            $before = $savedView->only(['name', 'filters', 'grouping', 'compact']);
            $savedView->update($attributes);
            $savedView->refresh();

            $this->recordAuditLog->handle(auditable: $savedView, action: 'saved_view.updated', actor: $actor, workspace: $project->workspace, before: $before, after: $savedView->only(['name', 'filters', 'grouping', 'compact']));
            $this->recordActivity->handle($project, 'saved_view.updated', $actor, $project->workspace, 'Saved View diperbarui.', ['name' => $savedView->name]);

            return $savedView;
        });
    }
}
