<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
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
            'number' => fake()->unique()->numberBetween(1, 9999),
            'identifier' => fake()->unique()->bothify('TASK-####'),
            'title' => fake()->sentence(4),
            'description' => null,
            'status' => TaskStatus::Backlog,
            'priority' => null,
            'assignee_id' => null,
            'requester_id' => User::factory(),
            'created_by_id' => User::factory(),
            'due_date' => null,
            'blocked_reason' => null,
            'canceled_reason' => null,
            'reopen_reason' => null,
            'completion_note' => null,
            'evidence_url' => null,
            'completed_at' => null,
            'parent_task_id' => null,
            'milestone_id' => null,
        ];
    }
}
