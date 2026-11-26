<?php

use App\Models\User;

test('a user can create the first unit without creating a workspace explicitly', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('units.store'), ['name' => 'Operasional TI']);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('units', ['name' => 'Operasional TI', 'created_by_id' => $user->id]);
    $this->assertDatabaseHas('unit_memberships', ['user_id' => $user->id]);
    expect($user->fresh()->active_unit_id)->not->toBeNull();
});

test('users cannot create another initial unit after joining a unit', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('units.store'), ['name' => 'Operasional TI'])->assertRedirect(route('dashboard'));

    $this->actingAs($user)->post(route('units.store'), ['name' => 'Infra'])->assertForbidden();
    $this->assertDatabaseCount('units', 1);
});
