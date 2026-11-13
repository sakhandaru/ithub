<?php

namespace App\Http\Requests;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return $workspace instanceof Workspace && ($this->user()?->can('update', $workspace) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'timezone' => ['required', 'string', Rule::timezone()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama Workspace wajib diisi.',
            'name.max' => 'Nama Workspace maksimal 120 karakter.',
            'timezone.required' => 'Timezone wajib dipilih.',
            'timezone.timezone' => 'Timezone yang dipilih tidak valid.',
        ];
    }
}
