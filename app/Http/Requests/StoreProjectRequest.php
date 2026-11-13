<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return $workspace instanceof Workspace && ($this->user()?->can('create', [Project::class, $workspace]) ?? false);
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
            'name' => ['required', 'string', 'max:120'],
            'identifier' => ['required', 'string', 'max:12', 'regex:/^[A-Z0-9]+$/', Rule::unique('projects')->where('workspace_id', $workspace instanceof Workspace ? $workspace->getKey() : null)],
            'unit_id' => ['required', 'string', Rule::exists('units', 'id')],
            'description' => ['nullable', 'string', 'max:10000'],
            'visibility' => ['required', Rule::enum(ProjectVisibility::class)],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'project_lead_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'default_assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['identifier' => strtoupper((string) $this->input('identifier'))]);
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $workspace = $this->route('workspace');
            $userIds = array_filter([(int) $this->input('project_lead_id'), (int) $this->input('default_assignee_id')]);

            if (! $workspace instanceof Workspace || ! $workspace->units()->whereKey($this->string('unit_id')->toString())->exists()) {
                $validator->errors()->add('unit_id', 'Unit harus berasal dari Workspace ini.');
            }

            foreach ($userIds as $userId) {
                if (! $workspace instanceof Workspace || ! $workspace->memberships()->where('user_id', $userId)->exists()) {
                    $validator->errors()->add('project_lead_id', 'Project Lead dan Default Assignee harus anggota Workspace.');
                    break;
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama Project wajib diisi.',
            'identifier.required' => 'Project ID wajib diisi.',
            'identifier.regex' => 'Project ID hanya boleh berisi huruf kapital dan angka.',
            'identifier.unique' => 'Project ID sudah digunakan di Workspace ini.',
            'unit_id.required' => 'Unit utama wajib dipilih.',
            'unit_id.exists' => 'Unit yang dipilih tidak ditemukan.',
            'visibility.enum' => 'Visibility Project tidak valid.',
            'status.enum' => 'Status Project tidak valid.',
            'target_date.after_or_equal' => 'Target selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
