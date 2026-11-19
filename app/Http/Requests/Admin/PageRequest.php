<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ContentRules;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    use ContentRules;

    protected function modelClass(): string
    {
        return Page::class;
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->route('id') ? 'pages.update' : 'pages.create');
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('title');
        $this->prepareSeoBooleans();
        $this->merge(['show_in_search' => $this->boolean('show_in_search')]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => $this->slugRules('pages'),
            'parent_id' => ['nullable', 'integer', 'exists:pages,id', Rule::notIn([$this->route('id')])],
            'template' => ['required', Rule::in(array_keys(Page::TEMPLATES))],
            'h1' => ['nullable', 'string', 'max:255'],
            'hero_layout' => ['required', Rule::in(array_keys(Page::HERO_LAYOUTS))],
            'hero_subheading' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'show_in_search' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            ...$this->publishingRules(),
            ...$this->seoRules(),
            ...$this->relationRules(),
        ];
    }
}
