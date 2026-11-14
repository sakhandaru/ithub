<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\SavedView;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSavedViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $savedView = $this->route('savedView');

        return $savedView instanceof SavedView && $this->user()?->can('update', $savedView) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $savedView = $this->route('savedView');
        $project = $this->route('project');

        return [
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('saved_views')
                    ->ignore($savedView instanceof SavedView ? $savedView->getKey() : null)
                    ->where(fn ($query) => $query
                        ->where('project_id', $project instanceof Project ? $project->getKey() : null)
                        ->where('user_id', $this->user()?->getKey())),
            ],
            'filters' => ['required', 'array:status,priority,assignee,requester,milestone,no_deadline,no_evidence,preset,search,sort,direction'],
            'filters.status' => ['nullable', 'string', 'max:30'],
            'filters.priority' => ['nullable', 'string', 'max:30'],
            'filters.assignee' => ['nullable', 'string', 'max:50'],
            'filters.requester' => ['nullable', 'string', 'max:50'],
            'filters.milestone' => ['nullable', 'string', 'max:50'],
            'filters.no_deadline' => ['nullable', 'boolean'],
            'filters.no_evidence' => ['nullable', 'boolean'],
            'filters.preset' => ['nullable', Rule::in(['', 'mine', 'overdue', 'blocked', 'unassigned'])],
            'filters.search' => ['nullable', 'string', 'max:200'],
            'filters.sort' => ['nullable', Rule::in(['created_at', 'due_date', 'identifier', 'title', 'status'])],
            'filters.direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'grouping' => ['required', Rule::in(['none', 'status', 'assignee', 'milestone'])],
            'compact' => ['required', 'boolean'],
        ];
    }
}
