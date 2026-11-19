<?php

namespace App\Http\Requests\Admin;

use App\Models\Redirect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('redirects.manage');
    }

    public function rules(): array
    {
        return [
            'old_url' => ['required', 'string', 'max:191', 'different:new_url', Rule::unique('redirects', 'old_url')->ignore($this->route('id'))],
            'new_url' => ['nullable', 'required_unless:status_code,410', 'string', 'max:500'],
            'status_code' => ['required', Rule::in([301, 302, 410])],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'old_url' => $this->old_url ? Redirect::normalizePath($this->old_url) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
