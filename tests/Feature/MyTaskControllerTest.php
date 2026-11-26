<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\User;

test('global and unit task scopes show tasks assigned to the current user', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $unit = $workspace->units->firstOrFail();
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $unit->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $project, ['title' => 'Review deployment', 'assignee_id' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('my-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MyTasks')
            ->where('scope', 'global')
            ->where('tasks.0.id', $task->id));

    $this->actingAs($owner)
        ->get(route('my-tasks', ['unit' => $unit->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MyTasks')
            ->where('scope', 'unit')
            ->where('unit.id', $unit->id)
            ->where('tasks.0.id', $task->id));
});
