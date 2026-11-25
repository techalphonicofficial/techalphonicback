<?php

namespace App\Http\Requests\Admin;

use App\Enums\LandingPageType;
use App\Http\Requests\Admin\Concerns\ContentRules;
use App\Models\LandingPage;
use App\Models\Page;
use App\Services\LandingPageGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LandingPageRequest extends FormRequest
{
    use ContentRules;

    protected function modelClass(): string
    {
        return LandingPage::class;
    }

    public function authorize(): bool
    {
        return $this->user()->can($this->route('id') ? 'landing_pages.update' : 'landing_pages.create');
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('title');
        $this->prepareSeoBooleans();
        foreach (['benefits', 'process', 'why_choose_us', 'sections'] as $r) {
            $this->cleanRepeater($r);
        }
        foreach (['faq_enabled', 'show_testimonials', 'show_industries'] as $b) {
            $this->merge([$b => $this->boolean($b)]);
        }
    }

    public function rules(): array
    {
        $type = LandingPageType::tryFrom((string) $this->input('page_type'));

        return [
            'page_type' => ['required', Rule::enum(LandingPageType::class)],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'location_id' => [$type?->needsLocation() ? 'required' : 'prohibited', 'nullable', 'integer', 'exists:locations,id'],
            'industry_id' => [$type?->needsIndustry() ? 'required' : 'prohibited', 'nullable', 'integer', 'exists:industries,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => $this->slugRules('landing_pages'),
            'h1' => ['nullable', 'string', 'max:255'],
            'hero_layout' => ['required', Rule::in(array_keys(Page::HERO_LAYOUTS))],
            'hero_subheading' => ['nullable', 'string', 'max:255'],
            'introduction' => ['nullable', 'string', 'max:5000'],
            'content' => ['nullable', 'string'],
            ...$this->repeaterRules('sections', ['heading', 'content']),
            ...$this->repeaterRules('benefits'),
            ...$this->repeaterRules('process'),
            ...$this->repeaterRules('why_choose_us'),
            'cta' => ['nullable', 'array'],
            'cta.heading' => ['nullable', 'string', 'max:255'],
            'cta.text' => ['nullable', 'string', 'max:500'],
            'cta.button_text' => ['nullable', 'string', 'max:60'],
            'cta.button_url' => ['nullable', 'string', 'max:255'],
            'featured_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'faq_enabled' => ['boolean'],
            'faq_title' => ['nullable', 'string', 'max:255'],
            'show_testimonials' => ['boolean'],
            'show_industries' => ['boolean'],
            ...$this->publishingRules(),
            ...$this->seoRules(),
            ...$this->relationRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.prohibited' => 'Location cannot be selected for "Service + Industry" pages. Clear Location or change Page type to "Service + Location + Industry".',
            'industry_id.prohibited' => 'Industry cannot be selected for "Service + Location" pages. Clear Industry or change Page type to "Service + Location + Industry".',
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $existing = $this->routeModel();
            $sameCombo = $existing
                && (int) $existing->service_id === (int) $this->input('service_id')
                && (int) $existing->location_id === (int) $this->input('location_id')
                && (int) $existing->industry_id === (int) $this->input('industry_id');

            if (! $sameCombo && app(LandingPageGenerator::class)->exists(
                (int) $this->input('service_id'), $this->integer('location_id') ?: null, $this->integer('industry_id') ?: null
            )) {
                $v->errors()->add('service_id', 'A page for this service / location / industry combination already exists.');
            }
        }];
    }
}
