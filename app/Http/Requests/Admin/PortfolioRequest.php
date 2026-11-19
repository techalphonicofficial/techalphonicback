<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ContentRules;
use App\Models\Portfolio;
use Illuminate\Foundation\Http\FormRequest;

class PortfolioRequest extends FormRequest
{
    use ContentRules;

    protected function modelClass(): string
    {
        return Portfolio::class;
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->route('id') ? 'portfolio.update' : 'portfolio.create');
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('title');
        $this->prepareSeoBooleans();
        $this->merge([
            'technologies' => array_values(array_filter(array_map('trim', explode(',', (string) $this->input('technologies_text'))))) ?: null,
            'gallery' => array_values(array_filter(array_map('intval', (array) $this->input('gallery', [])))) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => $this->slugRules('portfolios', flat: false),
            'client' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['integer', 'exists:media,id'],
            'technologies' => ['nullable', 'array'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'results' => ['nullable', 'string', 'max:2000'],
            'project_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            ...$this->publishingRules(),
            ...$this->seoRules(),
        ];
    }
}
