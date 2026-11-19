<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ContentRules;
use App\Models\Blog;
use Illuminate\Foundation\Http\FormRequest;

class BlogRequest extends FormRequest
{
    use ContentRules;

    protected function modelClass(): string
    {
        return Blog::class;
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->route('id') ? 'blogs.update' : 'blogs.create');
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('title');
        $this->prepareSeoBooleans();
        foreach (['show_toc', 'faq_enabled', 'is_featured'] as $b) {
            $this->merge([$b => $this->boolean($b)]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => $this->slugRules('blogs', flat: false),
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'author_id' => ['nullable', 'integer', 'exists:authors,id'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'show_toc' => ['boolean'],
            'faq_enabled' => ['boolean'],
            'is_featured' => ['boolean'],
            ...$this->publishingRules(),
            ...$this->seoRules(),
            ...$this->relationRules(),
        ];
    }
}
