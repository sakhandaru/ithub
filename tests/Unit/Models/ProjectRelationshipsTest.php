<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Models\Project;
use App\Models\User;

test('project relationships expose its workspace, unit, lead, and members', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $owner->id,
        'project_lead_id' => $owner->id,
    ]);
    $project->memberships()->create(['user_id' => $owner->id, 'role' => 'project_admin', 'joined_at' => now()]);

    expect($project->workspace->is($workspace))->toBeTrue()
        ->and($project->unit->is($workspace->units->first()))->toBeTrue()
        ->and($project->projectLead->is($owner))->toBeTrue()
        ->and($project->members)->toHaveCount(1);
});
