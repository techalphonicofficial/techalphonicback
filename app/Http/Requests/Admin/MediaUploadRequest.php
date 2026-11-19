<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaService;
use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.create');
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'max:'.config('site.max_upload_kb', 8192), 'mimetypes:'.implode(',', MediaService::ALLOWED_MIMES)],
            'folder' => ['nullable', 'string', 'max:100', 'alpha_dash'],
        ];
    }
}
