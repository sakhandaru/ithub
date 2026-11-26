<?php

namespace App\Actions\Modules;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateModule
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $actor, Project $project, array $attributes): Module
    {
        return DB::transaction(function () use ($actor, $project, $attributes): Module {
            $module = $project->modules()->create($attributes);
            $this->recordAuditLog->handle(auditable: $module, action: 'module.created', actor: $actor, workspace: $project->workspace, after: $module->only(['name', 'description']));
            $this->recordActivity->handle($project, 'module.created', $actor, $project->workspace, 'Modul dibuat.', ['name' => $module->name]);

            return $module;
        });
    }
}
