<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskRelation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskRelation>
 */
class TaskRelationFactory extends Factory
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
            'task_id' => Task::factory(),
            'related_task_id' => Task::factory(),
            'type' => 'related',
        ];
    }
}
