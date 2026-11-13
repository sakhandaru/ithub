<?php

namespace App\Http\Requests;

use App\Enums\ProjectRole;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && $this->user()?->can('assign', $task) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['assignee_id' => ['nullable', 'integer', 'exists:users,id']];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $task = $this->route('task');
            $assigneeId = $this->input('assignee_id');
            if (! $task instanceof Task || $assigneeId === null) {
                return;
            }

            $role = $task->project->memberships()->where('user_id', $assigneeId)->value('role');
            if (! in_array($role, [ProjectRole::ProjectAdmin->value, ProjectRole::Contributor->value], true)) {
                $validator->errors()->add('assignee_id', 'PIC Task harus Project Admin atau Contributor di Project ini.');
            }
        }];
    }
}
