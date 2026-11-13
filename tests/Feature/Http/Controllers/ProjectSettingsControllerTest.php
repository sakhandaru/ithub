<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Models\Project;
use App\Models\User;

test('project admins can update project settings', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $owner->id,
    ]);
    $project->memberships()->create(['user_id' => $owner->id, 'role' => ProjectRole::ProjectAdmin, 'joined_at' => now()]);

    $this->actingAs($owner)
        ->patch(route('workspaces.projects.update', [$workspace, $project]), [
            'name' => 'Updated Portal',
            'unit_id' => $workspace->units->first()->id,
            'visibility' => 'workspace',
            'status' => ProjectStatus::Planned->value,
            'project_lead_id' => $owner->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Updated Portal', 'status' => 'planned']);
});

test('contributors cannot update project settings or manage members', function () {
    $owner = User::factory()->create();
    $contributor = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $workspace->memberships()->create(['user_id' => $contributor->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $owner->id,
        'visibility' => ProjectVisibility::Workspace,
    ]);
    $membership = $project->memberships()->create(['user_id' => $contributor->id, 'role' => ProjectRole::Contributor, 'joined_at' => now()]);

    $this->actingAs($contributor)
        ->patch(route('workspaces.projects.update', [$workspace, $project]), ['name' => 'Nope'])
        ->assertForbidden();
    $this->actingAs($contributor)
        ->delete(route('workspaces.projects.members.destroy', [$workspace, $project, $membership]))
        ->assertForbidden();
});
