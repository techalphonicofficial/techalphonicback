<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ContentRules;
use App\Models\Industry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndustryRequest extends FormRequest
{
    use ContentRules;

    protected function modelClass(): string
    {
        return Industry::class;
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->route('id') ? 'industries.update' : 'industries.create');
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('name');
        foreach (['challenges', 'solutions', 'benefits'] as $r) {
            $this->cleanRepeater($r);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'plural_name' => ['nullable', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:120', 'regex:'.self::SLUG_REGEX, Rule::unique('industries', 'slug')->ignore($this->route('id'))],
            'description' => ['nullable', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image_id' => ['nullable', 'integer', 'exists:media,id'],
            ...$this->repeaterRules('challenges', ['title']),
            ...$this->repeaterRules('solutions', ['title']),
            ...$this->repeaterRules('benefits', ['title', 'text']),
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'faq_ids' => ['nullable', 'array'],
            'faq_ids.*' => ['integer', 'exists:faqs,id'],
            'status' => ['required', Rule::in(['published', 'draft'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
