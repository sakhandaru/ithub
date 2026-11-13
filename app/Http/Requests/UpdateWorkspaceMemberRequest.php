<?php

namespace App\Http\Requests;

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
            'role' => ['required', Rule::enum(WorkspaceRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Role Workspace wajib dipilih.',
            'role.enum' => 'Role Workspace tidak valid.',
        ];
    }
}
