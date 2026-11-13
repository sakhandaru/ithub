<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && $this->user()?->can('attach', $task) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $extensions = implode(',', config('sitop.attachments.allowed_extensions', []));

        return [
            'file' => ['required', 'file', 'max:'.config('sitop.attachments.max_size_kilobytes'), 'mimes:'.$extensions],
        ];
    }
}
