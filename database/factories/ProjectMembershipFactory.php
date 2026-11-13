<?php

namespace Database\Factories;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMembership>
 */
class ProjectMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'role' => ProjectRole::Contributor,
            'added_by_id' => null,
            'joined_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => ProjectRole::ProjectAdmin]);
    }

    public function commenter(): static
    {
        return $this->state(fn (): array => ['role' => ProjectRole::Commenter]);
    }

    public function viewer(): static
    {
        return $this->state(fn (): array => ['role' => ProjectRole::Viewer]);
    }
}
