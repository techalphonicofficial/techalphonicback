<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HeroSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pages.update');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active'), 'show_form' => $this->boolean('show_form')]);
    }

    public function rules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:255'],
            'subheading' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cta_text' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'secondary_cta_text' => ['nullable', 'string', 'max:60'],
            'secondary_cta_url' => ['nullable', 'string', 'max:255'],
            'image_id' => ['nullable', 'integer', 'exists:media,id'],
            'background_id' => ['nullable', 'integer', 'exists:media,id'],
            'background_video_id' => ['nullable', 'integer', 'exists:media,id'],
            'video_url' => ['nullable', 'string', 'max:255', 'regex:#^(https?://|/)#'],
            'badge' => ['nullable', 'string', 'max:120'],
            'trust_text' => ['nullable', 'string', 'max:255'],
            'show_form' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
