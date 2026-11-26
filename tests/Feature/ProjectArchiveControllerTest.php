<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Models\Project;
use App\Models\User;

test('project archive routes move a project between active and archived views', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $owner->id,
        'status' => 'active',
    ]);

    $this->actingAs($owner)
        ->post(route('workspaces.projects.archive', [$workspace, $project]))
        ->assertRedirect(route('workspaces.projects.show', [$workspace, $project]));

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'archived_by_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('workspaces.projects.restore', [$workspace, $project]))
        ->assertRedirect(route('workspaces.projects.show', [$workspace, $project]));

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'archived_at' => null, 'archived_by_id' => null]);
});
