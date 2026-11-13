<?php

namespace Database\Factories;

use App\Models\Unit;
use App\Models\UnitMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitMembership>
 */
class UnitMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'user_id' => User::factory(),
            'added_by_id' => null,
        ];
    }
}
