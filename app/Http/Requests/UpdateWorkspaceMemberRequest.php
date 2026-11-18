<?php

namespace App\Http\Requests;

use App\Enums\SystemRole;
use App\Enums\WorkspaceRole;
use App\Models\WorkspaceMembership;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $membership = $this->route('membership');

        return $membership instanceof WorkspaceMembership && ($this->user()?->can('update', $membership) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in([...array_column(SystemRole::cases(), 'value'), ...array_column(WorkspaceRole::cases(), 'value')])],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Role login wajib dipilih.',
            'role.enum' => 'Role login tidak valid.',
        ];
    }
}
