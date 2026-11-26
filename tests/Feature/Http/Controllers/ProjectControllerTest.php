<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
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
        'icon' => '🌐',
        'identifier' => 'PORTAL',
        'unit_id' => $unit->id,
        'visibility' => ProjectVisibility::Unit->value,
        'status' => 'draft',
    ]);

    $project = Project::query()->firstOrFail();

    $response->assertRedirect(route('workspaces.projects.index', $workspace));
    $this->actingAs($owner)
        ->get(route('workspaces.projects.show', [$workspace, $project]))
        ->assertRedirect(route('workspaces.projects.dashboard', [$workspace, $project]));
});

test('the project create route opens the create modal on the project index', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');

    $this->actingAs($owner)
        ->get(route('workspaces.projects.create', $workspace))
        ->assertRedirect(route('workspaces.projects.index', $workspace, absolute: false).'?create=1');
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

    $this->actingAs($unitMember)->get(route('workspaces.projects.show', [$workspace, $project]))->assertRedirect(route('workspaces.projects.dashboard', [$workspace, $project]));
    $this->actingAs($otherMember)->get(route('workspaces.projects.show', [$workspace, $project]))->assertNotFound();
});

test('project members can view a unit project even when they belong to another unit', function () {
    $owner = User::factory()->create();
    $projectMember = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $unit = $workspace->units->first();
    $workspace->memberships()->create(['user_id' => $projectMember->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $unit->id,
        'created_by_id' => $owner->id,
        'visibility' => ProjectVisibility::Unit,
    ]);
    $project->memberships()->create(['user_id' => $projectMember->id, 'role' => 'contributor', 'joined_at' => now()]);

    $this->actingAs($projectMember)->get(route('workspaces.projects.show', [$workspace, $project]))->assertRedirect(route('workspaces.projects.dashboard', [$workspace, $project]));
});

test('project overview redirects to the project dashboard', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace', 'status' => 'active',
    ]);
    $first = app(CreateTask::class)->handle($owner, $project, ['title' => 'Selesai']);
    $first->update(['status' => 'done']);
    $blockedTask = app(CreateTask::class)->handle($owner, $project, ['title' => 'Terblokir']);
    $blockedTask->update(['status' => 'blocked']);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.show', [$workspace, $project]))
        ->assertRedirect(route('workspaces.projects.dashboard', [$workspace, $project]));

    $this->actingAs($owner)
        ->get(route('workspaces.projects.dashboard', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('projects/Dashboard')->has('stats'));
});

test('project cycles and modules pages require project visibility', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);

    foreach (['cycles' => 'projects/Cycles', 'modules' => 'projects/Modules'] as $page => $component) {
        $this->actingAs($owner)
            ->get(route("workspaces.projects.{$page}", [$workspace, $project]))
            ->assertOk()
            ->assertInertia(fn ($inertia) => $inertia->component($component));

        $this->actingAs($outsider)
            ->get(route("workspaces.projects.{$page}", [$workspace, $project]))
            ->assertNotFound();
    }
});
