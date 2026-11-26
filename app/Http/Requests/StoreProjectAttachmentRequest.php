<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && $this->user()?->can('update', $project) === true;
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
