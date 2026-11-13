<?php

use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\RestoreProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\User;

test('archived projects remain readable but all task mutation routes are read-only', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, ['name' => 'Portal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id, 'visibility' => 'workspace', 'status' => 'completed']);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Existing task']);
    app(ArchiveProject::class)->handle($owner, $project->fresh());

    $this->actingAs($owner)->get(route('workspaces.projects.tasks.show', [$workspace, $project, $task]))->assertOk();
    $this->actingAs($owner)->patch(route('workspaces.projects.tasks.update', [$workspace, $project, $task]), ['title' => 'Tidak boleh'])->assertForbidden();
    $this->actingAs($owner)->post(route('workspaces.projects.tasks.store', [$workspace, $project]), ['title' => 'Tidak boleh'])->assertForbidden();
});

test('restoring a project returns mutation access', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, ['name' => 'Portal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id, 'visibility' => 'workspace', 'status' => 'completed']);
    app(ArchiveProject::class)->handle($owner, $project->fresh());
    app(RestoreProject::class)->handle($owner, $project->fresh());

    $this->actingAs($owner)->post(route('workspaces.projects.tasks.store', [$workspace, $project]), ['title' => 'Boleh lagi'])->assertRedirect();
});
