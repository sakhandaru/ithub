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
            'expected_outcome' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'priority' => ['sometimes', 'nullable', Rule::in(['urgent', 'high', 'medium', 'low'])],
            'requester_id' => ['sometimes', 'integer', 'exists:users,id'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'milestone_id' => ['sometimes', 'nullable', 'ulid', 'exists:milestones,id'],
            'module_id' => ['sometimes', 'nullable', 'ulid', 'exists:modules,id'],
            'release_id' => ['sometimes', 'nullable', 'ulid', 'exists:releases,id'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $task = $this->route('task');
            if (! $task instanceof Task) {
                return;
            }

            if ($this->has('requester_id') && ! $task->project->memberships()->where('user_id', $this->integer('requester_id'))->exists()) {
                $validator->errors()->add('requester_id', 'Pemohon harus menjadi anggota Project.');
            }

            if ($this->has('milestone_id') && $this->input('milestone_id') !== null && ! $task->project->milestones()->whereKey($this->input('milestone_id'))->exists()) {
                $validator->errors()->add('milestone_id', 'Milestone harus berasal dari Project yang sama.');
            }

            if ($this->has('module_id') && $this->input('module_id') !== null && ! $task->project->modules()->whereKey($this->input('module_id'))->exists()) {
                $validator->errors()->add('module_id', 'Modul harus berasal dari Project yang sama.');
            }

            if ($this->has('release_id') && $this->input('release_id') !== null && ! $task->project->releases()->whereKey($this->input('release_id'))->exists()) {
                $validator->errors()->add('release_id', 'Rilis harus berasal dari Project yang sama.');
            }

            $startDate = $this->input('start_date', $task->getRawOriginal('start_date'));
            $dueDate = $this->input('due_date', $task->getRawOriginal('due_date'));
            if ($startDate !== null && $dueDate !== null && $startDate > $dueDate) {
                $validator->errors()->add('start_date', 'Tanggal mulai tidak boleh setelah deadline.');
            }

        }];
    }
}
