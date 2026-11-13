<?php

use App\Actions\Projects\CreateProject;
use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ProjectRole;
use App\Models\Task;
use App\Models\User;

test('project contributors can create tasks but viewers cannot', function () {
    $owner = User::factory()->create();
    $contributor = User::factory()->create();
    $viewer = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $project = app(CreateProject::class)->handle($owner, $workspace, [
        'name' => 'Portal Internal',
        'identifier' => 'PORTAL',
        'unit_id' => $workspace->units->first()->id,
        'visibility' => 'workspace',
        'status' => 'active',
    ]);
    foreach ([$contributor, $viewer] as $user) {
        $workspace->memberships()->create(['user_id' => $user->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    }
    $project->memberships()->create(['user_id' => $contributor->id, 'role' => ProjectRole::Contributor, 'joined_at' => now()]);
    $project->memberships()->create(['user_id' => $viewer->id, 'role' => ProjectRole::Viewer, 'joined_at' => now()]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'requester_id' => $owner->id,
        'created_by_id' => $owner->id,
        'identifier' => 'PORTAL-1',
        'number' => 1,
    ]);

    expect($contributor->can('create', [Task::class, $project]))->toBeTrue()
        ->and($viewer->can('create', [Task::class, $project]))->toBeFalse()
        ->and($viewer->can('view', $task))->toBeTrue();
});
