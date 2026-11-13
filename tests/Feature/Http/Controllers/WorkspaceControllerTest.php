<?php

use App\Models\User;

test('users without a workspace are sent to workspace onboarding', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('workspaces.create'));
});

test('users can create a workspace with its initial units', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('workspaces.store'), [
        'name' => 'Tim Teknologi',
        'timezone' => 'Asia/Jakarta',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('workspaces', ['name' => 'Tim Teknologi', 'timezone' => 'Asia/Jakarta']);
    $this->assertDatabaseCount('units', 3);
    $this->assertDatabaseHas('workspace_memberships', ['user_id' => $user->id, 'role' => 'owner']);
    expect($user->fresh()->active_workspace_id)->not->toBeNull();
});

test('workspace creation returns validation errors for an invalid timezone', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->from(route('workspaces.create'))->post(route('workspaces.store'), [
        'name' => 'Tim Teknologi',
        'timezone' => 'Not/A/Timezone',
    ]);

    $response->assertSessionHasErrors('timezone');
    $this->assertDatabaseCount('workspaces', 0);
});
