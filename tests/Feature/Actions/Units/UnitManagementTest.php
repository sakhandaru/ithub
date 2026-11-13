<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\WorkspaceRole;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkspaceMembership;

test('workspace admins can create units and add workspace members to them', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $member = User::factory()->create();
    WorkspaceMembership::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => WorkspaceRole::Member]);

    $createResponse = $this->actingAs($owner)->post(route('workspaces.units.store', $workspace), ['name' => 'Service Desk']);
    $unit = Unit::query()->where('workspace_id', $workspace->id)->where('name', 'Service Desk')->firstOrFail();
    $memberResponse = $this->actingAs($owner)->post(route('workspaces.units.members.store', [$workspace, $unit]), ['user_id' => $member->id]);

    $createResponse->assertRedirect();
    $memberResponse->assertRedirect();
    $this->assertDatabaseHas('unit_memberships', ['unit_id' => $unit->id, 'user_id' => $member->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'unit.member.added', 'workspace_id' => $workspace->id]);
});

test('a user from another workspace cannot create a unit through a direct URL', function () {
    $owner = User::factory()->create();
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->post(route('workspaces.units.store', $workspace), ['name' => 'Unauthorized']);

    $response->assertNotFound();
    $this->assertDatabaseMissing('units', ['name' => 'Unauthorized']);
});
