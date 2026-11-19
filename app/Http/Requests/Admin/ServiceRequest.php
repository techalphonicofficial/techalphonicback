<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ContentRules;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    use ContentRules;

    protected function modelClass(): string
    {
        return Service::class;
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->route('id') ? 'services.update' : 'services.create');
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('name');
        $this->prepareSeoBooleans();
        foreach (['hero_highlights', 'benefits', 'features', 'process', 'technologies', 'pricing'] as $r) {
            $this->cleanRepeater($r);
        }
        $this->merge(['is_featured' => $this->boolean('is_featured')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => $this->slugRules('services'),
            'menu_label' => ['nullable', 'string', 'max:100'],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'parent_id' => ['nullable', 'integer', 'exists:services,id', Rule::notIn([$this->route('id')])],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'hero_heading' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string', 'max:1000'],
            'hero_layout' => ['required', Rule::in(array_keys(Page::HERO_LAYOUTS))],
            'hero_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'content' => ['nullable', 'string'],
            ...$this->repeaterRules('hero_highlights', ['title', 'text', 'icon']),
            ...$this->repeaterRules('benefits'),
            ...$this->repeaterRules('features', ['title', 'text', 'icon', 'url']),
            ...$this->repeaterRules('process'),
            ...$this->repeaterRules('technologies', ['title']),
            ...$this->repeaterRules('pricing', ['title', 'text', 'value', 'suffix', 'features', 'url', 'cta_text', 'featured']),
            'cta_heading' => ['nullable', 'string', 'max:255'],
            'cta_description' => ['nullable', 'string', 'max:1000'],
            'landing_template' => ['nullable', 'array'],
            'landing_template.slug_base' => ['nullable', 'string', 'max:60', 'regex:'.self::SLUG_REGEX],
            'landing_template.why_choose_us' => ['nullable', 'array'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            ...$this->publishingRules(),
            ...$this->seoRules(),
            ...$this->relationRules(),
        ];
    }
}
