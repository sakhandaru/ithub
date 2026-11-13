<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && $this->user()?->can('transition', $task) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'reason' => ['nullable', 'string', 'max:5000'],
            'completion_note' => ['nullable', 'string', 'max:10000'],
            'evidence_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
