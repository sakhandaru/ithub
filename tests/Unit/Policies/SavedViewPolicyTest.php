<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ProjectVisibility;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\SavedView;
use App\Models\User;
use App\Models\WorkspaceMembership;
use App\Policies\SavedViewPolicy;

function savedViewProject(User $owner): Project
{
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');

    return Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $owner->id,
        'visibility' => ProjectVisibility::Workspace,
    ]);
}

test('saved views are private to their creator while retaining project visibility rules', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $project = savedViewProject($owner);
    WorkspaceMembership::factory()->create([
        'workspace_id' => $project->workspace_id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member,
    ]);
    $savedView = SavedView::factory()->create([
        'workspace_id' => $project->workspace_id,
        'project_id' => $project->id,
        'user_id' => $owner->id,
    ]);

    $policy = new SavedViewPolicy;

    expect($policy->view($owner, $savedView))->toBeTrue()
        ->and($policy->view($member, $savedView))->toBeFalse()
        ->and($policy->view($outsider, $savedView))->toBeFalse();
});

test('saved view creation and listing require project visibility', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();
    $project = savedViewProject($owner);
    $policy = new SavedViewPolicy;

    expect($policy->create($owner, $project))->toBeTrue()
        ->and($policy->viewAny($owner, $project))->toBeTrue()
        ->and($policy->create($outsider, $project))->toBeFalse()
        ->and($policy->viewAny($outsider, $project))->toBeFalse();
});

test('only the creator can update or delete a saved view', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = savedViewProject($owner);
    $savedView = SavedView::factory()->create([
        'workspace_id' => $project->workspace_id,
        'project_id' => $project->id,
        'user_id' => $owner->id,
    ]);
    $policy = new SavedViewPolicy;

    expect($policy->update($owner, $savedView))->toBeTrue()
        ->and($policy->delete($owner, $savedView))->toBeTrue()
        ->and($policy->update($otherUser, $savedView))->toBeFalse()
        ->and($policy->delete($otherUser, $savedView))->toBeFalse();
});
