<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
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
            'subject_type' => 'task',
            'subject_id' => (string) Str::ulid(),
            'action' => 'task.updated',
            'description' => 'Task diperbarui.',
            'properties' => [],
            'created_at' => now(),
        ];
    }
}
