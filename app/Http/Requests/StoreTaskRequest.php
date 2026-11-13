<?php

namespace App\Http\Requests;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && $this->user()?->can('create', [Task::class, $project]) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['nullable', 'string', Rule::in(['urgent', 'high', 'medium', 'low'])],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'requester_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['ulid', 'exists:labels,id'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $project = $this->route('project');
            if (! $project instanceof Project) {
                return;
            }

            foreach (['assignee_id', 'requester_id'] as $field) {
                $userId = $this->integer($field);
                if ($userId === 0) {
                    continue;
                }

                $membership = $project->memberships()->where('user_id', $userId)->first();
                if ($field === 'assignee_id' && (! $membership || ! in_array((string) $membership->getRawOriginal('role'), [ProjectRole::ProjectAdmin->value, ProjectRole::Contributor->value], true))) {
                    $validator->errors()->add($field, 'PIC Task harus Project Admin atau Contributor di Project ini.');
                }
                if ($field === 'requester_id' && ! $membership) {
                    $validator->errors()->add($field, 'Pemohon harus menjadi anggota Project.');
                }
            }

            $labelIds = $this->input('label_ids', []);
            if ($labelIds !== [] && $project->labels()->whereIn('id', $labelIds)->count() !== count($labelIds)) {
                $validator->errors()->add('label_ids', 'Label harus berasal dari Project yang sama.');
            }
        }];
    }
}
