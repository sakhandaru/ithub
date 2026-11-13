<?php

namespace App\Http\Requests;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUnitMemberRequest extends FormRequest
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
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $unit = $this->route('unit');
            $member = User::query()->find($this->integer('user_id'));

            if (! $unit instanceof Unit || ! $member) {
                return;
            }

            if (! $unit->workspace->memberships()->whereBelongsTo($member)->exists()) {
                $validator->errors()->add('user_id', 'User harus menjadi anggota Workspace terlebih dahulu.');

                return;
            }

            if ($unit->memberships()->whereBelongsTo($member)->exists()) {
                $validator->errors()->add('user_id', 'User tersebut sudah menjadi anggota Unit.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Anggota wajib dipilih.',
            'user_id.exists' => 'Anggota yang dipilih tidak ditemukan.',
        ];
    }
}
