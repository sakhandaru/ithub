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
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['ulid', 'exists:labels,id'],
            'parent_task_id' => ['sometimes', 'nullable', 'ulid', 'exists:tasks,id'],
            'milestone_id' => ['sometimes', 'nullable', 'ulid', 'exists:milestones,id'],
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

            $labelIds = $this->input('label_ids', []);
            if ($labelIds !== [] && $task->project->labels()->whereIn('id', $labelIds)->count() !== count($labelIds)) {
                $validator->errors()->add('label_ids', 'Label harus berasal dari Project yang sama.');
            }

            if ($this->has('milestone_id') && $this->input('milestone_id') !== null && ! $task->project->milestones()->whereKey($this->input('milestone_id'))->exists()) {
                $validator->errors()->add('milestone_id', 'Milestone harus berasal dari Project yang sama.');
            }

            $startDate = $this->input('start_date', $task->getRawOriginal('start_date'));
            $dueDate = $this->input('due_date', $task->getRawOriginal('due_date'));
            if ($startDate !== null && $dueDate !== null && $startDate > $dueDate) {
                $validator->errors()->add('start_date', 'Tanggal mulai tidak boleh setelah deadline.');
            }

            if (! $this->has('parent_task_id') || $this->input('parent_task_id') === null) {
                return;
            }

            $parent = $task->project->tasks()->whereKey($this->input('parent_task_id'))->first();
            if (! $parent) {
                $validator->errors()->add('parent_task_id', 'Parent Task harus berasal dari Project yang sama.');

                return;
            }

            $visited = [];
            while ($parent !== null) {
                if ($parent->is($task) || in_array($parent->id, $visited, true)) {
                    $validator->errors()->add('parent_task_id', 'Hierarchy Task tidak boleh membentuk siklus.');
                    break;
                }
                $visited[] = $parent->id;
                $parent = $parent->parentTask()->first();
            }
        }];
    }
}
