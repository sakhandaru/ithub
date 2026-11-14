<?php

namespace App\Http\Requests;

use App\Enums\ProjectRole;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkUpdateTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && $this->user()?->can('update', $project) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'task_ids' => ['required', 'array', 'min:1', 'max:100'],
            'task_ids.*' => ['required', 'ulid', 'distinct', 'exists:tasks,id'],
            'priority' => ['sometimes', 'nullable', Rule::in(['urgent', 'high', 'medium', 'low'])],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $project = $this->route('project');
            $taskIds = $this->input('task_ids', []);

            if (! $project instanceof Project) {
                return;
            }

            if (! $this->has('priority') && ! $this->has('assignee_id')) {
                $validator->errors()->add('task_ids', 'Pilih perubahan yang ingin diterapkan.');
            }

            if ($taskIds !== [] && $project->tasks()->whereIn('id', $taskIds)->count() !== count($taskIds)) {
                $validator->errors()->add('task_ids', 'Semua Task harus berasal dari Project yang sama.');
            }

            if ($this->has('assignee_id') && $this->input('assignee_id') !== null) {
                $isEligible = $project->memberships()
                    ->where('user_id', $this->integer('assignee_id'))
                    ->whereIn('role', [ProjectRole::ProjectAdmin->value, ProjectRole::Contributor->value])
                    ->exists();
                if (! $isEligible) {
                    $validator->errors()->add('assignee_id', 'PIC Task harus Project Admin atau Contributor di Project ini.');
                }
            }
        }];
    }
}
