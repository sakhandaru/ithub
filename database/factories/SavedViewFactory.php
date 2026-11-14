<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\SavedView;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedView>
 */
class SavedViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'filters' => [],
            'grouping' => 'none',
            'compact' => false,
        ];
    }
}
