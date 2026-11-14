<?php

namespace App\Actions\SavedViews;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\SavedView;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteSavedView
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    public function handle(User $actor, SavedView $savedView): void
    {
        DB::transaction(function () use ($actor, $savedView): void {
            $project = $savedView->project;
            $before = $savedView->only(['id', 'name', 'filters', 'grouping', 'compact']);

            $this->recordAuditLog->handle(auditable: $project, action: 'saved_view.deleted', actor: $actor, workspace: $project->workspace, before: $before);
            $savedView->delete();
            $this->recordActivity->handle($project, 'saved_view.deleted', $actor, $project->workspace, 'Saved View dihapus.', ['name' => $before['name']]);
        });
    }
}
