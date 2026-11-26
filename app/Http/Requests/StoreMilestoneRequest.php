<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMilestoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ($project = $this->route('project')) instanceof Project && $this->user()?->can('update', $project) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return ['name' => ['required', 'string', 'max:120'], 'target_date' => ['nullable', 'date'], 'release_id' => ['nullable', 'ulid', Rule::exists('releases', 'id')->where('project_id', $project instanceof Project ? $project->id : null)]];
    }
}
