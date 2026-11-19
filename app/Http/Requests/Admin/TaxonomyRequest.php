<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class TaxonomyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('blogs.update') || $this->user()->can('faqs.update');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->slug ?: $this->name)]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
