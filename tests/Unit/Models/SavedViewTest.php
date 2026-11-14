<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Models\Project;
use App\Models\SavedView;
use App\Models\User;

test('saved view scopes constrain results to a project and user', function () {
    $user = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($user, 'Engineering', 'Asia/Jakarta');
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $user->id,
    ]);
    $otherProject = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $user->id,
    ]);
    $otherUser = User::factory()->create();

    SavedView::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'user_id' => $user->id,
        'name' => 'My view',
    ]);
    SavedView::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $otherProject->id,
        'user_id' => $user->id,
        'name' => 'Other project view',
    ]);
    SavedView::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'user_id' => $otherUser->id,
        'name' => 'Other user view',
    ]);

    $views = SavedView::query()->forProject($project)->forUser($user)->get();

    expect($views)->toHaveCount(1)
        ->and($views->first()->name)->toBe('My view');
});

test('saved view casts filters and compact mode', function () {
    $savedView = SavedView::factory()->create([
        'filters' => ['status' => 'blocked', 'no_evidence' => 1],
        'compact' => true,
    ]);

    expect($savedView->filters)->toBe(['status' => 'blocked', 'no_evidence' => 1])
        ->and($savedView->compact)->toBeTrue();
});
