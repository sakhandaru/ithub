<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReleaseRequest extends FormRequest
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
        return ['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:2000'], 'target_date' => ['nullable', 'date'], 'status' => ['required', 'in:draft,planned,released,canceled']];
    }
}
