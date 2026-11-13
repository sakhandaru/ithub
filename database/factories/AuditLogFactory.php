<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
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
            'actor_id' => User::factory(),
            'action' => 'workspace.updated',
            'auditable_type' => Workspace::class,
            'auditable_id' => (string) Str::ulid(),
            'request_id' => (string) Str::ulid(),
            'before' => null,
            'after' => ['name' => fake()->company()],
        ];
    }
}
