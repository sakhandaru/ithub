<?php

namespace App\Actions\Projects;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ArchiveProject
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    public function handle(User $actor, Project $project): Project
    {
        return DB::transaction(function () use ($actor, $project): Project {
            $status = ProjectStatus::from((string) $project->getRawOriginal('status'));

            if (! in_array($status, [ProjectStatus::Completed, ProjectStatus::Canceled], true)) {
                throw ValidationException::withMessages(['project' => 'Hanya Project Completed atau Canceled yang dapat diarsipkan.']);
            }

            $project->update(['archived_at' => now(), 'archived_by_id' => $actor->getKey()]);
            $this->recordAuditLog->handle(
                auditable: $project,
                action: 'project.archived',
                actor: $actor,
                workspace: $project->workspace,
                after: ['archived_at' => CarbonImmutable::parse((string) $project->getRawOriginal('archived_at'))->toIso8601String()],
            );
            $this->recordActivity->handle($project, 'project.archived', $actor, $project->workspace, 'Project diarsipkan.');

            return $project;
        });
    }
}
