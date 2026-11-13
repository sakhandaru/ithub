<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Models\User;
use App\Models\WorkspaceMembership;

test('a user can switch to another workspace they belong to', function () {
    $user = User::factory()->create();
    $first = app(CreateWorkspace::class)->handle($user, 'First Workspace', 'Asia/Jakarta');
    $secondOwner = User::factory()->create();
    $second = app(CreateWorkspace::class)->handle($secondOwner, 'Second Workspace', 'Asia/Jakarta');
    WorkspaceMembership::factory()->create(['workspace_id' => $second->id, 'user_id' => $user->id]);

    $response = $this->actingAs($user)->put(route('workspaces.active.update', $second));

    $response->assertRedirect(route('dashboard'));
    expect($user->fresh()->active_workspace_id)->toBe($second->id)
        ->and($first->id)->not->toBe($second->id);
});

test('a user cannot switch to a workspace they do not belong to', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Private Workspace', 'Asia/Jakarta');
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->put(route('workspaces.active.update', $workspace));

    $response->assertNotFound();
});
