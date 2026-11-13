<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && $this->user()?->can('update', $task) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'priority' => ['sometimes', 'nullable', Rule::in(['urgent', 'high', 'medium', 'low'])],
            'requester_id' => ['sometimes', 'integer', 'exists:users,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['ulid', 'exists:labels,id'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $task = $this->route('task');
            if (! $task instanceof Task || ! $this->has('requester_id')) {
                return;
            }

            if (! $task->project->memberships()->where('user_id', $this->integer('requester_id'))->exists()) {
                $validator->errors()->add('requester_id', 'Pemohon harus menjadi anggota Project.');
            }

            $labelIds = $this->input('label_ids', []);
            if ($labelIds !== [] && $task->project->labels()->whereIn('id', $labelIds)->count() !== count($labelIds)) {
                $validator->errors()->add('label_ids', 'Label harus berasal dari Project yang sama.');
            }
        }];
    }
}
