<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ContentRules;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;

class ServiceCategoryRequest extends FormRequest
{
    use ContentRules;

    protected function modelClass(): string
    {
        return ServiceCategory::class;
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->route('id') ? 'services.update' : 'services.create');
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('name');
        $this->prepareSeoBooleans();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => $this->slugRules('service_categories'),
            'menu_label' => ['nullable', 'string', 'max:100'],
            'h1' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'image_id' => ['nullable', 'integer', 'exists:media,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            ...$this->publishingRules(),
            ...$this->seoRules(),
            ...$this->relationRules(),
        ];
    }
}
