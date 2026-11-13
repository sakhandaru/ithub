<?php

namespace App\Http\Requests;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return $workspace instanceof Workspace && ($this->user()?->can('manageUnits', $workspace) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('units')->where('workspace_id', $workspace instanceof Workspace ? $workspace->getKey() : null),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama Unit wajib diisi.',
            'name.max' => 'Nama Unit maksimal 80 karakter.',
            'name.unique' => 'Nama Unit sudah digunakan di Workspace ini.',
        ];
    }
}
