<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\LandingPageType;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Location;
use App\Models\Service;
use App\Models\UrlSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds programmatic SEO pages from Service + Location/Industry.
 *
 * Content is NOT rendered at generation time — {{variables}} are stored so admins can edit the template text,
 * and they're resolved on every request. Every generated page starts as Draft + noindex + excluded from sitemap,
 * so nothing thin goes live without a human review.
 */
class LandingPageGenerator
{
    public const DEFAULT_TEMPLATES = [
        'service_location' => [
            'slug' => '{{service_slug}}-company-in-{{location_slug}}',
            'title' => '{{service_name}} Company in {{location_name}}',
            'h1' => '{{service_name}} Company in {{location_name}}',
            'hero_subheading' => 'Local expertise. Measurable growth.',
            'introduction' => '<p>Looking for a reliable {{service_name_lower}} partner in {{location_name}}? {{site_name}} helps businesses across {{location_name}} and {{state_name}} grow with strategies built around local search behaviour, competition and your goals — not generic packages.</p>',
            'meta_title' => '{{service_name}} Company in {{location_name}} | {{site_name}}',
            'meta_description' => 'Grow your business in {{location_name}} with {{site_name}}\'s {{service_name_lower}} services. Transparent reporting, local strategy and a dedicated team. Get a free consultation.',
            'focus_keyword' => '{{service_name_lower}} company in {{location_name}}',
        ],
        'service_industry' => [
            'slug' => '{{service_slug}}-for-{{industry_slug}}',
            'title' => '{{service_name}} for {{industry_plural}}',
            'h1' => '{{service_name}} for {{industry_plural}}',
            'hero_subheading' => 'Strategies built for your industry.',
            'introduction' => '<p>{{industry_plural}} face unique challenges online. Our {{service_name_lower}} programmes are shaped around how your customers search, compare and decide — so every rupee of effort moves real business metrics.</p>',
            'meta_title' => '{{service_name}} for {{industry_plural}} | {{site_name}}',
            'meta_description' => 'Specialised {{service_name_lower}} for {{industry_plural}}. Industry-specific strategy, proven process and transparent reporting from {{site_name}}.',
            'focus_keyword' => '{{service_name_lower}} for {{industry_name}}',
        ],
        'service_location_industry' => [
            'slug' => '{{service_slug}}-for-{{industry_slug}}-in-{{location_slug}}',
            'title' => '{{service_name}} for {{industry_plural}} in {{location_name}}',
            'h1' => '{{service_name}} for {{industry_plural}} in {{location_name}}',
            'hero_subheading' => 'Industry know-how meets local insight.',
            'introduction' => '<p>We help {{industry_plural}} in {{location_name}} win more customers through {{service_name_lower}} that reflects both the local market and the way your industry buys.</p>',
            'meta_title' => '{{service_name}} for {{industry_plural}} in {{location_name}}',
            'meta_description' => '{{service_name}} for {{industry_plural}} in {{location_name}}. Local market insight, industry-specific strategy and measurable results with {{site_name}}.',
            'focus_keyword' => '{{service_name_lower}} for {{industry_name}} in {{location_name}}',
        ],
    ];

    public function __construct(private TemplateRenderer $renderer) {}

    /** Attributes for a new page — shown in the admin form for review before saving. */
    public function draft(LandingPageType $type, Service $service, ?Location $location = null, ?Industry $industry = null): array
    {
        $tpl = array_merge(self::DEFAULT_TEMPLATES[$type->value], (array) data_get($service->landing_template, $type->value, []));

        $slugVars = [
            'service_slug' => $this->serviceSlugBase($service),
            'location_slug' => $location?->slug ?? '',
            'industry_slug' => $industry?->slug ?? '',
        ];
        $nameVars = [
            'service_name' => $service->name,
            'location_name' => $location?->name ?? '',
            'industry_plural' => $industry?->plural_name ?: ($industry?->name ?? ''),
        ];

        return [
            'page_type' => $type->value,
            'service_id' => $service->id,
            'location_id' => $location?->id,
            'industry_id' => $industry?->id,
            'slug' => $this->uniqueSlug(Str::slug($this->renderer->render($tpl['slug'], $slugVars))),
            // Title is stored rendered (used in admin lists, menus, link anchors); body copy keeps {{variables}}.
            'title' => $this->renderer->render($tpl['title'], $nameVars),
            'h1' => $tpl['h1'],
            'hero_subheading' => $tpl['hero_subheading'],
            'introduction' => $tpl['introduction'],
            'benefits' => $service->benefits,
            'process' => $service->process,
            'why_choose_us' => data_get($service->landing_template, 'why_choose_us'),
            'cta' => ['heading' => $service->cta_heading, 'text' => $service->cta_description],
            'faq_enabled' => true,
            'featured_image_id' => $location?->hero_image_id ?? $industry?->image_id ?? $service->hero_image_id,
            'status' => ContentStatus::Draft->value,
            'seo' => [
                'meta_title' => $tpl['meta_title'],
                'meta_description' => $tpl['meta_description'],
                'focus_keyword' => $tpl['focus_keyword'],
                'robots_index' => false,
                'robots_follow' => true,
                'include_in_sitemap' => false,
            ],
        ];
    }

    public function create(LandingPageType $type, Service $service, ?Location $location, ?Industry $industry, string $generatedBy = 'generator'): ?LandingPage
    {
        if ($this->exists($service->id, $location?->id, $industry?->id)) {
            return null;
        }

        return DB::transaction(function () use ($type, $service, $location, $industry, $generatedBy) {
            $data = $this->draft($type, $service, $location, $industry);
            $seo = $data['seo'];
            unset($data['seo']);

            $page = LandingPage::create($data + ['generated_by' => $generatedBy, 'created_by' => auth()->id()]);
            $page->syncSeo($seo);

            return $page;
        });
    }

    /**
     * Bulk: one service × many locations and/or industries. Always drafts.
     *
     * @return array{created: int, skipped: int, ids: int[]}
     */
    public function bulk(LandingPageType $type, Service $service, array $locationIds, array $industryIds): array
    {
        $locations = $type->needsLocation() ? Location::whereIn('id', $locationIds)->get() : collect([null]);
        $industries = $type->needsIndustry() ? Industry::whereIn('id', $industryIds)->get() : collect([null]);
        $created = $skipped = 0;
        $ids = [];

        foreach ($locations as $location) {
            foreach ($industries as $industry) {
                if ($page = $this->create($type, $service, $location, $industry, 'bulk')) {
                    $created++;
                    $ids[] = $page->id;
                } else {
                    $skipped++;
                }
            }
        }

        return compact('created', 'skipped', 'ids');
    }

    public function exists(int $serviceId, ?int $locationId, ?int $industryId): bool
    {
        // MySQL unique indexes allow repeated NULLs, so the combination check must happen here too.
        return LandingPage::withTrashed()->where('service_id', $serviceId)
            ->where(fn ($q) => $locationId ? $q->where('location_id', $locationId) : $q->whereNull('location_id'))
            ->where(fn ($q) => $industryId ? $q->where('industry_id', $industryId) : $q->whereNull('industry_id'))
            ->exists();
    }

    /** "search-engine-optimization" → "seo" style short base, configurable per service in landing_template.slug_base */
    private function serviceSlugBase(Service $service): string
    {
        return data_get($service->landing_template, 'slug_base') ?: $service->slug;
    }

    private function uniqueSlug(string $slug): string
    {
        $candidate = $slug;
        $i = 2;
        while (! UrlSlug::isAvailable($candidate) || LandingPage::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }
}
