<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\User;

test('global search only returns objects in the active workspace scope', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, ['name' => 'Portal', 'identifier' => 'PORTAL', 'unit_id' => $workspace->units->first()->id, 'visibility' => 'workspace', 'status' => 'active']);
    app(CreateTask::class)->handle($owner, $project, ['title' => 'Deploy portal']);

    $this->actingAs($owner)->get(route('search', ['q' => 'portal']))->assertRedirect(route('dashboard'));
    $this->actingAs($owner)->getJson(route('search', ['q' => 'portal']))->assertOk()->assertJsonCount(1, 'projects')->assertJsonCount(1, 'tasks');
});
