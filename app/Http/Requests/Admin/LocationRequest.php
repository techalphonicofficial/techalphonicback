<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ContentRules;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

        // An area always sits in its city's state and country, and area names repeat between cities ("Sector 62"),
        // so a new area's automatic slug gets the city added: sector-62-noida.
        $city = $this->input('type') === 'area' ? Location::where('type', 'city')->find($this->integer('parent_id')) : null;
        if ($city) {
            $this->merge(['state' => $city->state, 'country' => $city->country]);
            if (! $this->route('id') && $this->input('slug') === Str::slug(Str::lower((string) $this->input('name')))) {
                $this->merge(['slug' => $this->input('slug').'-'.$city->slug]);
            }
        }
        $this->merge([
            'local_facts' => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $this->input('local_facts_text'))))) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:'.self::SLUG_REGEX, Rule::unique('locations', 'slug')->ignore($this->route('id'))],
            'parent_id' => $this->input('type') === 'area'
                ? ['required', 'integer', Rule::exists('locations', 'id')->where('type', 'city')->whereNull('deleted_at')]
                : ['nullable', 'integer', 'exists:locations,id'],
            'type' => ['required', Rule::in(array_keys(Location::TYPES))],
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

    public function messages(): array
    {
        return [
            'parent_id.required' => 'Select the city this area belongs to.',
            'parent_id.exists' => 'The selected parent is not valid for this location type.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $existing = $this->routeModel();
            if (! $existing) {
                return;
            }
            if ((int) $this->input('parent_id') === (int) $existing->getKey()) {
                $v->errors()->add('parent_id', 'A location cannot be its own parent.');
            }
            if ($this->input('type') !== 'city' && $existing->areas()->exists()) {
                $v->errors()->add('type', 'This city has areas under it. Move or delete them before changing its type.');
            }
        }];
    }
}
