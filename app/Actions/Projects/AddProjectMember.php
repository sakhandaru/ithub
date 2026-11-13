<?php

namespace App\Actions\Projects;

use App\Actions\Audit\RecordAuditLog;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddProjectMember
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Project $project, User $member, ProjectRole $role): ProjectMembership
    {
        return DB::transaction(function () use ($actor, $project, $member, $role): ProjectMembership {
            if (! $project->workspace->memberships()->whereBelongsTo($member)->exists()) {
                throw ValidationException::withMessages(['user_id' => 'User harus menjadi anggota Workspace terlebih dahulu.']);
            }

            $membership = $project->memberships()->create([
                'user_id' => $member->getKey(),
                'role' => $role,
                'added_by_id' => $actor->getKey(),
                'joined_at' => now(),
            ]);
            $this->recordAuditLog->handle(
                auditable: $membership,
                action: 'project.member.added',
                actor: $actor,
                workspace: $project->workspace,
                after: ['project_id' => $project->getKey(), 'user_id' => $member->getKey(), 'role' => $role->value],
            );

            return $membership;
        });
    }
}
