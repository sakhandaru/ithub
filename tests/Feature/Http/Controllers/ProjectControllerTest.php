<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ProjectVisibility;
use App\Models\Project;
use App\Models\User;

test('workspace administrators can create and view a project', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $unit = $workspace->units->first();

    $response = $this->actingAs($owner)->post(route('workspaces.projects.store', $workspace), [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $unit->id,
        'visibility' => ProjectVisibility::Unit->value,
        'status' => 'draft',
    ]);

    $project = Project::query()->firstOrFail();

    $response->assertRedirect(route('workspaces.projects.index', $workspace));
    $this->actingAs($owner)
        ->get(route('workspaces.projects.show', [$workspace, $project]))
        ->assertOk();
});

test('a user outside the project workspace cannot resolve the project route', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $owner->id,
    ]);

    $this->actingAs($outsider)
        ->get(route('workspaces.projects.show', [$workspace, $project]))
        ->assertNotFound();
});

test('unit visibility is limited to members of the project unit', function () {
    $owner = User::factory()->create();
    $unitMember = User::factory()->create();
    $otherMember = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $unit = $workspace->units->first();
    $unit->memberships()->create(['user_id' => $unitMember->id, 'added_by_id' => $owner->id]);
    $workspace->memberships()->create(['user_id' => $unitMember->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $workspace->memberships()->create(['user_id' => $otherMember->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $unit->id,
        'created_by_id' => $owner->id,
        'visibility' => ProjectVisibility::Unit,
    ]);

    $this->actingAs($unitMember)->get(route('workspaces.projects.show', [$workspace, $project]))->assertOk();
    $this->actingAs($otherMember)->get(route('workspaces.projects.show', [$workspace, $project]))->assertNotFound();
});
