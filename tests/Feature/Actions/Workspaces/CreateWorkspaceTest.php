<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use RuntimeException;

test('creating a workspace records its owner, initial units, active preference, and audit log', function () {
    $user = User::factory()->create();

    $workspace = app(CreateWorkspace::class)->handle($user, 'Engineering', 'Asia/Jakarta');

    expect($workspace->units->pluck('name')->all())->toBe(['Development', 'Infra', 'Operasional']);
    $this->assertDatabaseHas('workspace_memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'workspace_id' => $workspace->id,
        'action' => 'workspace.created',
        'actor_id' => $user->id,
    ]);
    expect($user->fresh()->active_workspace_id)->toBe($workspace->id);
});

test('workspace creation rolls back when an initial unit cannot be created', function () {
    Event::listen('eloquent.creating: '.Unit::class, function (): void {
        throw new RuntimeException('Unit creation failed.');
    });

    $user = User::factory()->create();

    expect(fn () => app(CreateWorkspace::class)->handle($user, 'Engineering', 'Asia/Jakarta'))
        ->toThrow(RuntimeException::class);

    Event::forget('eloquent.creating: '.Unit::class);
    $this->assertDatabaseCount('workspaces', 0);
    $this->assertDatabaseCount('workspace_memberships', 0);
    $this->assertDatabaseCount('audit_logs', 0);
});
