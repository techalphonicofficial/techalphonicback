<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ContentRules;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocationRequest extends FormRequest
{
    use ContentRules;

    protected function modelClass(): string
    {
        return Location::class;
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->route('id') ? 'locations.update' : 'locations.create');
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('name');
        $this->merge([
            'local_facts' => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $this->input('local_facts_text'))))) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:'.self::SLUG_REGEX, Rule::unique('locations', 'slug')->ignore($this->route('id'))],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
            'type' => ['required', Rule::in(['city', 'state', 'country', 'region'])],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'hero_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'local_facts' => ['nullable', 'array', 'max:12'],
            'local_facts.*' => ['string', 'max:300'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['published', 'draft'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
