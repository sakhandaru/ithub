<?php

namespace App\Actions\Projects;

use App\Actions\Activity\RecordActivity;
use App\Actions\Audit\RecordAuditLog;
use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateProject
{
    public function __construct(private RecordAuditLog $recordAuditLog, private RecordActivity $recordActivity) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $creator, Workspace $workspace, array $attributes): Project
    {
        return DB::transaction(function () use ($creator, $workspace, $attributes): Project {
            $unitId = (string) $attributes['unit_id'];
            $unit = $workspace->units()->whereKey($unitId)->first();

            if (! $unit) {
                throw ValidationException::withMessages(['unit_id' => 'Unit harus berasal dari Workspace ini.']);
            }

            $project = $workspace->projects()->create([
                'unit_id' => $unit->getKey(),
                'name' => (string) $attributes['name'],
                'identifier' => strtoupper((string) $attributes['identifier']),
                'description' => $attributes['description'] ?? null,
                'visibility' => $attributes['visibility'] ?? ProjectVisibility::Unit,
                'status' => $attributes['status'] ?? ProjectStatus::Draft,
                'project_lead_id' => $attributes['project_lead_id'] ?? $creator->getKey(),
                'default_assignee_id' => $attributes['default_assignee_id'] ?? null,
                'created_by_id' => $creator->getKey(),
                'start_date' => $attributes['start_date'] ?? null,
                'target_date' => $attributes['target_date'] ?? null,
            ]);

            $project->memberships()->create([
                'user_id' => $creator->getKey(),
                'role' => ProjectRole::ProjectAdmin,
                'added_by_id' => $creator->getKey(),
                'joined_at' => now(),
            ]);

            $memberIds = array_filter([
                $project->project_lead_id,
                $project->default_assignee_id,
            ], fn (int|string|null $id): bool => $id !== null && (string) $id !== (string) $creator->getKey());

            foreach (array_unique($memberIds) as $memberId) {
                if (! $workspace->memberships()->where('user_id', $memberId)->exists()) {
                    throw ValidationException::withMessages(['project_lead_id' => 'Project Lead dan Default Assignee harus anggota Workspace.']);
                }

                $project->memberships()->create([
                    'user_id' => $memberId,
                    'role' => ProjectRole::Contributor,
                    'added_by_id' => $creator->getKey(),
                    'joined_at' => now(),
                ]);
            }

            $this->recordAuditLog->handle(
                auditable: $project,
                action: 'project.created',
                actor: $creator,
                workspace: $workspace,
                after: $project->only(['name', 'identifier', 'visibility', 'status', 'unit_id']),
            );
            $this->recordActivity->handle($project, 'project.created', $creator, $workspace, 'Project dibuat.', ['identifier' => $project->identifier]);

            return $project->load(['unit', 'projectLead', 'defaultAssignee', 'memberships.user']);
        });
    }
}
