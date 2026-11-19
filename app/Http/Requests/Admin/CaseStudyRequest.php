<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ContentRules;
use App\Models\CaseStudy;
use Illuminate\Foundation\Http\FormRequest;

class CaseStudyRequest extends FormRequest
{
    use ContentRules;

    protected function modelClass(): string
    {
        return CaseStudy::class;
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->route('id') ? 'case_studies.update' : 'case_studies.create');
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('title');
        $this->prepareSeoBooleans();
        $this->cleanRepeater('metrics');
        $this->merge([
            'technologies' => array_values(array_filter(array_map('trim', explode(',', (string) $this->input('technologies_text'))))) ?: null,
            'gallery' => array_values(array_filter(array_map('intval', (array) $this->input('gallery', [])))) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => $this->slugRules('case_studies', flat: false),
            'client' => ['nullable', 'string', 'max:255'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'challenge' => ['nullable', 'string'],
            'solution' => ['nullable', 'string'],
            'results' => ['nullable', 'string'],
            'technologies' => ['nullable', 'array'],
            ...$this->repeaterRules('metrics', ['label', 'value']),
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['integer', 'exists:media,id'],
            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'testimonial_id' => ['nullable', 'integer', 'exists:testimonials,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            ...$this->publishingRules(),
            ...$this->seoRules(),
        ];
    }
}
