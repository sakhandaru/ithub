<?php

namespace App\Actions\Modules;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteModule
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    public function handle(User $actor, Module $module): void
    {
        DB::transaction(function () use ($actor, $module): void {
            $project = $module->project;
            $this->recordAuditLog->handle(auditable: $project, action: 'module.deleted', actor: $actor, workspace: $project->workspace, before: $module->only(['id', 'name', 'description']));
            $module->delete();
            $this->recordActivity->handle($project, 'module.deleted', $actor, $project->workspace, 'Modul dihapus.', ['name' => $module->name]);
        });
    }
}
