<?php

namespace App\Http\Requests;

use App\Models\Unit;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $unit = $this->route('unit');

        return $unit instanceof Unit && ($this->user()?->can('update', $unit) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('units')
                    ->where('workspace_id', $this->route('workspace') instanceof Workspace ? $this->route('workspace')->getKey() : null)
                    ->ignore($this->route('unit')),
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
