<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Policies\WorkspacePolicy;

test('workspace visibility is granted only to members', function () {
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $workspace = Workspace::factory()->create();
    WorkspaceMembership::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => WorkspaceRole::Member]);

    $policy = new WorkspacePolicy;

    expect($policy->view($member, $workspace))->toBeTrue()
        ->and($policy->view($outsider, $workspace))->toBeFalse();
});

test('only owner and workspace admin can manage workspace settings', function (WorkspaceRole $role, bool $expected) {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    WorkspaceMembership::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => $role]);

    expect((new WorkspacePolicy)->update($user, $workspace))->toBe($expected);
})->with([
    'owner' => [WorkspaceRole::Owner, true],
    'workspace admin' => [WorkspaceRole::WorkspaceAdmin, true],
    'member' => [WorkspaceRole::Member, false],
    'guest' => [WorkspaceRole::Guest, false],
]);
