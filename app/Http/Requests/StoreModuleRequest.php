<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModuleRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return ['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:2000'], 'release_id' => ['nullable', 'ulid', Rule::exists('releases', 'id')->where('project_id', $project instanceof Project ? $project->id : null)]];
    }
}
