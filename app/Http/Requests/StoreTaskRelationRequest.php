<?php

namespace App\Http\Requests;

use App\Enums\TaskRelationType;
use App\Models\Task;
use App\Models\TaskRelation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTaskRelationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ($task = $this->route('task')) instanceof Task && $this->user()?->can('create', [TaskRelation::class, $task]) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['related_task_id' => ['required', 'ulid', 'exists:tasks,id'], 'type' => ['required', Rule::enum(TaskRelationType::class)]];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $task = $this->route('task');
            if (! $task instanceof Task) {
                return;
            }
            $related = $task->project->tasks()->whereKey($this->input('related_task_id'))->first();
            if (! $related) {
                $validator->errors()->add('related_task_id', 'Task harus berasal dari Project yang sama.');

                return;
            }
            if ($related->is($task)) {
                $validator->errors()->add('related_task_id', 'Task tidak dapat berelasi dengan dirinya sendiri.');

                return;
            }
            if ($task->relations()->where('related_task_id', $related->id)->where('type', $this->input('type'))->exists() || $related->relations()->where('related_task_id', $task->id)->where('type', $this->input('type'))->exists()) {
                $validator->errors()->add('related_task_id', 'Relasi Task tersebut sudah ada.');
            }
        }];
    }
}
