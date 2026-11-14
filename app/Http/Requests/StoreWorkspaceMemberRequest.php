<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWorkspaceMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return $workspace instanceof Workspace && ($this->user()?->can('manageMembers', $workspace) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', Rule::exists('users', 'email')],
            'role' => ['required', Rule::enum(WorkspaceRole::class)],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $workspace = $this->route('workspace');
            $member = User::query()->where('email', $this->string('email')->toString())->first();

            if ($workspace instanceof Workspace && $member && $workspace->memberships()->whereBelongsTo($member)->exists()) {
                $validator->errors()->add('email', 'User tersebut sudah menjadi anggota Workspace.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email anggota wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'User dengan email tersebut belum terdaftar di ithub.',
            'role.required' => 'Role Workspace wajib dipilih.',
            'role.enum' => 'Role Workspace tidak valid.',
        ];
    }
}
