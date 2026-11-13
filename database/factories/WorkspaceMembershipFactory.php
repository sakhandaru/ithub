<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceMembership>
 */
class WorkspaceMembershipFactory extends Factory
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
            'user_id' => User::factory(),
            'role' => WorkspaceRole::Member,
            'invited_by_id' => null,
            'joined_at' => now(),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => ['role' => WorkspaceRole::Owner]);
    }

    public function workspaceAdmin(): static
    {
        return $this->state(fn (): array => ['role' => WorkspaceRole::WorkspaceAdmin]);
    }

    public function guest(): static
    {
        return $this->state(fn (): array => ['role' => WorkspaceRole::Guest]);
    }
}
