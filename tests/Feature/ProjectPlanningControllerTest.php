<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\User;

test('the legacy inbox route redirects to backlog tasks', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $backlogTask = app(CreateTask::class)->handle($owner, $project, ['title' => 'Task backlog']);
    $backlogTask->update(['status' => 'backlog']);

    $target = route('workspaces.projects.tasks.index', [
        'workspace' => $workspace,
        'project' => $project,
        'view' => 'list',
        'status' => 'backlog',
    ]);

    $this->actingAs($owner)
        ->get(route('workspaces.projects.inbox', [$workspace, $project]))
        ->assertRedirect($target);

    $this->actingAs($owner)
        ->get($target)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $backlogTask->id)
            ->where('filters.status', 'backlog')
            ->where('view', 'list'));
});
