<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('blogs.update');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->slug ?: $this->name)]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('authors', 'slug')->ignore($this->route('id'))],
            'designation' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'avatar_id' => ['nullable', 'integer', 'exists:media,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
