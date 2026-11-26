<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\ProjectVisibility;
use App\Enums\SystemRole;
use App\Models\Project;
use App\Models\User;

test('system roles are distinct from project roles', function (): void {
    $admin = User::factory()->create(['system_role' => SystemRole::Admin]);
    $user = User::factory()->create(['system_role' => SystemRole::User]);
    $auditor = User::factory()->create(['system_role' => SystemRole::Auditor]);

    expect($admin->isAdmin())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse()
        ->and($auditor->isAuditor())->toBeTrue();
});

test('admin can see and manage organization objects without legacy membership', function (): void {
    $admin = User::factory()->create(['system_role' => SystemRole::Admin]);
    $workspace = app(CreateWorkspace::class)->handle($admin, 'Engineering', 'Asia/Jakarta');
    $unit = $workspace->units->first();

    expect($admin->can('view', $workspace))->toBeTrue()
        ->and($admin->can('update', $unit))->toBeTrue()
        ->and($admin->can('create', [Project::class, $workspace]))->toBeTrue();
});

test('auditor can read an assigned project but cannot mutate it', function (): void {
    $owner = User::factory()->create(['system_role' => SystemRole::Admin]);
    $auditor = User::factory()->create(['system_role' => SystemRole::Auditor]);
    $workspace = app(CreateWorkspace::class)->handle($owner, 'Engineering', 'Asia/Jakarta');
    $workspace->memberships()->create([
        'user_id' => $auditor->id,
        'role' => 'member',
        'invited_by_id' => $owner->id,
        'joined_at' => now(),
    ]);
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'unit_id' => $workspace->units->first()->id,
        'created_by_id' => $owner->id,
        'visibility' => ProjectVisibility::Private,
    ]);
    $project->memberships()->create(['user_id' => $auditor->id, 'role' => 'viewer', 'joined_at' => now()]);

    expect($auditor->can('view', $project))->toBeTrue()
        ->and($auditor->can('update', $project))->toBeFalse();
});
test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
