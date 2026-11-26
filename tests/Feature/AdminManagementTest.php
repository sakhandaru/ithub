<?php

use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\SystemRole;
use App\Models\User;

test('only admins can access global management areas', function (): void {
    $admin = User::factory()->create(['system_role' => SystemRole::Admin]);
    $user = User::factory()->create(['system_role' => SystemRole::User]);

    $this->actingAs($admin)->get(route('admin.management', 'users'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Management')->where('section', 'users'));

    $this->actingAs($user)->get(route('admin.management', 'users'))->assertForbidden();
});

test('admins can update login roles but the last admin cannot be demoted', function (): void {
    $admin = User::factory()->create(['system_role' => SystemRole::Admin]);
    $user = User::factory()->create(['system_role' => SystemRole::User]);

    $this->actingAs($admin)->put(route('admin.users.role.update', $user), ['system_role' => SystemRole::Auditor->value])
        ->assertRedirect();
    expect($user->fresh()->system_role)->toBe(SystemRole::Auditor);

    $this->actingAs($admin)->put(route('admin.users.role.update', $admin), ['system_role' => SystemRole::User->value])
        ->assertSessionHasErrors('system_role');
});

test('admin management units includes project and member counts', function (): void {
    $admin = User::factory()->create(['system_role' => SystemRole::Admin]);
    app(CreateWorkspace::class)->handle($admin, 'Engineering', 'Asia/Jakarta');

    $this->actingAs($admin)->get(route('admin.management', 'units'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Management')->where('section', 'units')->has('units'));
});
test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
