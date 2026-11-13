<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceMembership;

test('workspace admins can view and update workspace settings', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');

    $view = $this->actingAs($owner)->get(route('workspaces.settings.show', $workspace));
    $update = $this->actingAs($owner)->patch(route('workspaces.update', $workspace), [
        'name' => 'Engineering Platform',
        'timezone' => 'Asia/Makassar',
    ]);

    $view->assertOk()->assertInertia(fn ($page) => $page
        ->component('workspaces/Settings')
        ->where('workspace.name', 'Engineering')
        ->has('memberships', 1)
        ->has('units', 3));
    $update->assertRedirect();
    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'name' => 'Engineering Platform', 'timezone' => 'Asia/Makassar']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'workspace.updated', 'workspace_id' => $workspace->id]);
});

test('workspace members cannot view workspace management settings', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $member = User::factory()->create();
    WorkspaceMembership::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => WorkspaceRole::Member]);

    $response = $this->actingAs($member)->get(route('workspaces.settings.show', $workspace));

    $response->assertForbidden();
});
