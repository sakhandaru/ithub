<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'uploaded_by_id' => User::factory(),
            'disk' => 'attachments',
            'path' => 'tasks/example/file.txt',
            'original_name' => 'file.txt',
            'mime_type' => 'text/plain',
            'size' => 12,
        ];
    }
}
