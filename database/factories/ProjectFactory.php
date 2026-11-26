<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
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
            'unit_id' => Unit::factory(),
            'name' => fake()->sentence(3),
            'icon' => '📁',
            'identifier' => strtoupper(fake()->unique()->lexify('???')),
            'description' => fake()->optional()->sentence(),
            'visibility' => ProjectVisibility::Unit,
            'status' => ProjectStatus::Draft,
            'project_lead_id' => null,
            'default_assignee_id' => null,
            'created_by_id' => User::factory(),
            'start_date' => null,
            'target_date' => null,
            'archived_at' => null,
            'archived_by_id' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ProjectStatus::Active,
            'archived_at' => now(),
        ]);
    }
}
