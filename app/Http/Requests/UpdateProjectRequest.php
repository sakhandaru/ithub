<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && ($this->user()?->can('update', $project) ?? false);
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

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $project = $this->route('project');
            $workspace = $this->route('workspace');
            $userIds = array_filter([(int) $this->input('project_lead_id'), (int) $this->input('default_assignee_id')]);

            if ($project instanceof Project && $workspace instanceof Workspace && ! $workspace->units()->whereKey($this->string('unit_id')->toString())->exists()) {
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
            'unit_id.required' => 'Unit utama wajib dipilih.',
            'unit_id.exists' => 'Unit yang dipilih tidak ditemukan.',
            'visibility.enum' => 'Visibility Project tidak valid.',
            'status.enum' => 'Status Project tidak valid.',
            'target_date.after_or_equal' => 'Target selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
