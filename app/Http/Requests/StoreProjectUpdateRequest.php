<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProjectUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && $this->user()?->can('update', $project) === true && ! $project->isArchived();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['title' => ['nullable', 'string', 'max:160'], 'health' => ['required', 'in:on_track,at_risk,off_track'], 'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'], 'summary' => ['required', 'string', 'max:10000'], 'accomplishments' => ['nullable', 'string', 'max:10000'], 'risks' => ['nullable', 'string', 'max:5000'], 'decisions_needed' => ['nullable', 'string', 'max:5000'], 'next_steps' => ['nullable', 'string', 'max:5000'], 'next_update_at' => ['nullable', 'date', 'after_or_equal:period_end'], 'mentioned_user_ids' => ['nullable', 'array'], 'mentioned_user_ids.*' => ['integer', 'distinct', 'exists:users,id']];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $project = $this->route('project');
            if (! $project instanceof Project || $validator->errors()->has('mentioned_user_ids')) {
                return;
            }

            $userIds = collect($this->input('mentioned_user_ids', []))->map(fn (mixed $id): int => (int) $id)->values();
            if ($userIds->isEmpty()) {
                return;
            }

            if ($project->memberships()->whereIn('user_id', $userIds)->count() !== $userIds->count()) {
                $validator->errors()->add('mentioned_user_ids', 'Orang yang ditag harus menjadi anggota Project.');
            }
        }];
    }
}
