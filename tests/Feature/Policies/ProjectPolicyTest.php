<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ProjectVisibility;
use App\Models\Project;
use App\Models\User;

test('workspace owners can create projects but ordinary members cannot', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $workspace->memberships()->create(['user_id' => $member->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);

    expect($owner->can('create', [Project::class, $workspace]))->toBeTrue()
        ->and($member->can('create', [Project::class, $workspace]))->toBeFalse();
});

test('private projects are visible only to project members and workspace administrators', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $workspace->memberships()->create(['user_id' => $member->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $workspace->memberships()->create(['user_id' => $outsider->id, 'role' => 'member', 'invited_by_id' => $owner->id, 'joined_at' => now()]);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $owner->id,
        'visibility' => ProjectVisibility::Private,
    ]);
    $project->memberships()->create(['user_id' => $member->id, 'role' => 'viewer', 'joined_at' => now()]);

    expect($member->can('view', $project))->toBeTrue()
        ->and($owner->can('view', $project))->toBeTrue()
        ->and($outsider->can('view', $project))->toBeFalse();
});
