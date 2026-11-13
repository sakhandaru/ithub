<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceMembership;
use App\Policies\WorkspacePolicy;

test('workspace admins can add existing users and the change is audited', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $member = User::factory()->create(['email' => 'member@example.com']);

    expect((new WorkspacePolicy)->manageMembers($owner, $workspace))->toBeTrue();

    $response = $this->actingAs($owner)->post(route('workspaces.members.store', $workspace), [
        'email' => $member->email,
        'role' => WorkspaceRole::Member->value,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('workspace_memberships', ['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => WorkspaceRole::Member->value]);
    $this->assertDatabaseHas('audit_logs', ['workspace_id' => $workspace->id, 'action' => 'workspace.member.added']);
});

test('members cannot manage workspace membership', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $member = User::factory()->create();
    WorkspaceMembership::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => WorkspaceRole::Member]);
    $newUser = User::factory()->create();

    $response = $this->actingAs($member)->post(route('workspaces.members.store', $workspace), ['email' => $newUser->email, 'role' => WorkspaceRole::Member->value]);

    $response->assertForbidden();
});

test('a user outside a workspace receives not found for its settings', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Private Engineering', 'Asia/Jakarta');
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->get(route('workspaces.settings.show', $workspace));

    $response->assertNotFound();
});

test('an owner can change a member role and remove that member without losing audit history', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $member = User::factory()->create();
    $membership = WorkspaceMembership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member,
    ]);

    $roleResponse = $this->actingAs($owner)->patch(route('workspaces.members.update', [$workspace, $membership]), ['role' => WorkspaceRole::WorkspaceAdmin->value]);
    $removeResponse = $this->actingAs($owner)->delete(route('workspaces.members.destroy', [$workspace, $membership]));

    $roleResponse->assertRedirect();
    $removeResponse->assertRedirect();
    $this->assertDatabaseMissing('workspace_memberships', ['id' => $membership->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'workspace.member.role_updated', 'workspace_id' => $workspace->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'workspace.member.removed', 'workspace_id' => $workspace->id]);
});
