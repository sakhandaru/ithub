<?php

namespace App\Http\Requests;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Workspace::class) ?? false;
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
