<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Workspaces\CreateWorkspace;
use App\Models\Task;
use App\Models\User;

test('a contributor can create and transition a task through project routes', function () {
    $owner = User::factory()->create();
    $contributor = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $workspace->memberships()->create(['user_id' => $contributor->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $project->memberships()->create(['user_id' => $contributor->id, 'role' => 'contributor', 'joined_at' => now()]);

    $response = $this->actingAs($contributor)->post(route('workspaces.projects.tasks.store', [$workspace, $project]), [
        'title' => 'Dokumentasikan perubahan',
    ]);

    $task = Task::query()->firstOrFail();
    $response->assertRedirect();
    expect($task->identifier)->toBe('PORTAL-1');

    $this->actingAs($contributor)
        ->post(route('workspaces.projects.tasks.transition', [$workspace, $project, $task]), [
            'status' => 'todo',
        ])
        ->assertRedirect();

    expect($task->fresh()->status->value)->toBe('todo');
});

test('task route does not expose a task from another project', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $otherProject = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Infra Internal',
        'identifier' => 'INFRA',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    $task = app(CreateTask::class)->handle($owner, $otherProject, ['title' => 'Task lain']);

    $this->actingAs($owner)
        ->patch(route('workspaces.projects.tasks.update', [$workspace, $project, $task]), ['title' => 'Tidak boleh'])
        ->assertNotFound();
});
