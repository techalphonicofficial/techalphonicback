<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\CaseStudy;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\SchemaTemplate;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SocialLink;
use App\Support\ContentCache;
use App\Support\SeoData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Builds one JSON-LD @graph per page. Global nodes (Organization, WebSite) are referenced by @id, so nothing is
 * duplicated, and FAQPage is only emitted when FaqService says FAQs are rendered on the page.
 */
class SchemaService
{
    public function __construct(
        private SettingsService $settings,
        private FaqService $faqs,
        private TemplateRenderer $renderer,
    ) {}

    public function orgId(): string
    {
        return url('/').'#organization';
    }

    public function websiteId(): string
    {
        return url('/').'#website';
    }

    /** @return array<int, array> */
    public function forModel(Model $model, SeoData $seo, ?SeoMeta $meta): array
    {
        $graph = $this->globalNodes();
        $pageId = $seo->canonical.'#webpage';

        $graph[] = $this->webPage($seo, $this->webPageType($model));
        if (count($seo->breadcrumbs) > 1) {
            $graph[] = $this->breadcrumbList($seo);
        }

        if ($meta && ! $meta->schema_enabled) {
            return $graph;
        }

        $node = match (true) {
            $model instanceof Service, $model instanceof ServiceCategory => $this->service($model, $seo),
            $model instanceof LandingPage => $this->landingService($model, $seo),
            $model instanceof Blog => $this->article($model, $seo),
            $model instanceof Portfolio, $model instanceof CaseStudy => $this->creativeWork($model, $seo),
            default => null,
        };
        if ($node) {
            $graph[] = $node;
        }

        $faqs = $this->faqs->for($model);
        if ($faqs->isNotEmpty()) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $seo->canonical.'#faq',
                'isPartOf' => ['@id' => $pageId],
                'mainEntity' => $faqs->map(fn ($f) => [
                    '@type' => 'Question',
                    'name' => strip_tags($f->question),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f->answer)],
                ])->all(),
            ];
        }

        $vars = $this->variables($model, $seo);
        foreach ($this->templatesFor($model) as $template) {
            $graph = array_merge($graph, $this->decodeCustom($template->json_template, $vars));
        }
        if ($meta?->schema_json) {
            $graph = array_merge($graph, $this->decodeCustom($meta->schema_json, $vars));
        }

        return $this->dedupe($graph);
    }

    public function forListing(SeoData $seo, string $type = 'WebPage', array $items = []): array
    {
        $graph = $this->globalNodes();
        $page = $this->webPage($seo, $type);
        if ($items) {
            $page['mainEntity'] = [
                '@type' => 'ItemList',
                'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
                    '@type' => 'ListItem', 'position' => $i + 1, 'url' => $item['url'], 'name' => $item['name'],
                ])->all(),
            ];
        }
        $graph[] = $page;
        if (count($seo->breadcrumbs) > 1) {
            $graph[] = $this->breadcrumbList($seo);
        }

        return $graph;
    }

    public function globalNodes(): array
    {
        return ContentCache::remember('schema.global', function () {
            $s = $this->settings;
            $logo = $s->media('logo')?->url;
            $org = array_filter([
                '@type' => $s->get('organization_type', 'Organization'),
                '@id' => $this->orgId(),
                'name' => $s->get('site_name'),
                'url' => url('/'),
                'logo' => $logo ? ['@type' => 'ImageObject', 'url' => $logo] : null,
                'image' => $logo,
                'slogan' => $s->get('site_tagline'),
                'telephone' => $s->get('phone'),
                'email' => $s->get('email'),
                'foundingDate' => $s->get('founding_year'),
                'priceRange' => $s->get('organization_type') !== 'Organization' ? $s->get('price_range', '₹₹') : null,
                'address' => $s->get('address_line') ? [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $s->get('address_line'),
                    'addressLocality' => $s->get('address_locality'),
                    'addressRegion' => $s->get('address_region'),
                    'postalCode' => $s->get('postal_code'),
                    'addressCountry' => $s->get('country_code', 'IN'),
                ] : null,
                'geo' => $s->get('latitude') && $s->get('longitude') && $s->get('organization_type') !== 'Organization' ? [
                    '@type' => 'GeoCoordinates', 'latitude' => (float) $s->get('latitude'), 'longitude' => (float) $s->get('longitude'),
                ] : null,
                'openingHours' => $s->get('organization_type') !== 'Organization' ? $s->get('opening_hours_schema') : null,
                'sameAs' => SocialLink::active()->pluck('url')->all() ?: null,
                'contactPoint' => $s->get('phone') ? [
                    '@type' => 'ContactPoint', 'telephone' => $s->get('phone'), 'contactType' => 'sales',
                    'email' => $s->get('email'), 'areaServed' => $s->get('country_code', 'IN'), 'availableLanguage' => ['en', 'hi'],
                ] : null,
            ]);

            $website = [
                '@type' => 'WebSite',
                '@id' => $this->websiteId(),
                'url' => url('/'),
                'name' => $s->get('site_name'),
                'publisher' => ['@id' => $this->orgId()],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/search').'?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ];

            return [$org, $website];
        });
    }

    private function webPage(SeoData $seo, string $type): array
    {
        return array_filter([
            '@type' => $type,
            '@id' => $seo->canonical.'#webpage',
            'url' => $seo->canonical,
            'name' => $seo->title,
            'description' => $seo->description,
            'isPartOf' => ['@id' => $this->websiteId()],
            'about' => ['@id' => $this->orgId()],
            'primaryImageOfPage' => $seo->ogImage ? ['@type' => 'ImageObject', 'url' => $seo->ogImage] : null,
            'breadcrumb' => count($seo->breadcrumbs) > 1 ? ['@id' => $seo->canonical.'#breadcrumb'] : null,
            'datePublished' => $seo->publishedTime,
            'dateModified' => $seo->modifiedTime,
            'inLanguage' => 'en-IN',
        ]);
    }

    private function breadcrumbList(SeoData $seo): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $seo->canonical.'#breadcrumb',
            'itemListElement' => collect($seo->breadcrumbs)->values()->map(fn ($c, $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $c['name'],
                'item' => $c['url'] ?? $seo->canonical,
            ]))->all(),
        ];
    }

    private function webPageType(Model $model): string
    {
        if ($model instanceof Page) {
            return match ($model->template) {
                'contact' => 'ContactPage',
                'about' => 'AboutPage',
                'hub' => 'CollectionPage',
                default => 'WebPage',
            };
        }

        return $model instanceof ServiceCategory ? 'CollectionPage' : 'WebPage';
    }

    private function service(Model $model, SeoData $seo): array
    {
        return array_filter([
            '@type' => 'Service',
            '@id' => $seo->canonical.'#service',
            'name' => $model->name,
            'serviceType' => $model->name,
            'description' => $seo->description,
            'url' => $seo->canonical,
            'image' => $seo->ogImage,
            'provider' => ['@id' => $this->orgId()],
            'areaServed' => ['@type' => 'Country', 'name' => $this->settings->get('country', 'India')],
            'mainEntityOfPage' => ['@id' => $seo->canonical.'#webpage'],
        ]);
    }

    private function landingService(LandingPage $page, SeoData $seo): array
    {
        $node = $this->service($page->service ?? $page, $seo);
        $node['name'] = $this->renderer->render($page->heading, $page->templateVariables());
        if ($page->location) {
            $node['areaServed'] = array_filter([
                '@type' => $page->location->type === 'country' ? 'Country' : ($page->location->type === 'state' ? 'State' : 'City'),
                'name' => $page->location->name,
                'geo' => $page->location->latitude ? [
                    '@type' => 'GeoCoordinates', 'latitude' => $page->location->latitude, 'longitude' => $page->location->longitude,
                ] : null,
            ]);
        }
        if ($page->industry) {
            $node['audience'] = ['@type' => 'BusinessAudience', 'audienceType' => $page->industry->plural_name ?: $page->industry->name];
        }

        return $node;
    }

    private function article(Blog $blog, SeoData $seo): array
    {
        return array_filter([
            '@type' => 'BlogPosting',
            '@id' => $seo->canonical.'#article',
            'headline' => Str::limit($blog->title, 110, ''),
            'description' => $seo->description,
            'image' => $seo->ogImage ? [$seo->ogImage] : null,
            'datePublished' => $blog->published_at?->toIso8601String(),
            'dateModified' => $blog->updated_at?->toIso8601String(),
            'author' => $blog->author ? ['@type' => 'Person', 'name' => $blog->author->name] : ['@id' => $this->orgId()],
            'publisher' => ['@id' => $this->orgId()],
            'mainEntityOfPage' => ['@id' => $seo->canonical.'#webpage'],
            'articleSection' => $blog->category?->name,
            'keywords' => $blog->tags->pluck('name')->implode(', ') ?: null,
            'wordCount' => str_word_count(strip_tags((string) $blog->content)),
            'inLanguage' => 'en-IN',
        ]);
    }

    private function creativeWork(Model $model, SeoData $seo): array
    {
        return array_filter([
            '@type' => 'CreativeWork',
            '@id' => $seo->canonical.'#work',
            'name' => $model->title,
            'description' => $seo->description,
            'image' => $seo->ogImage,
            'creator' => ['@id' => $this->orgId()],
            'url' => $seo->canonical,
        ]);
    }

    private function templatesFor(Model $model)
    {
        $appliesTo = $model->getMorphClass();

        return ContentCache::remember('schema.templates', fn () => SchemaTemplate::where('is_active', true)
            ->where('applies_to', '!=', 'global')->orderBy('sort_order')->get())
            ->where('applies_to', $appliesTo);
    }

    public function variables(Model $model, SeoData $seo): array
    {
        $vars = [
            'site_name' => $this->settings->get('site_name'),
            'site_url' => url('/'),
            'page_title' => $seo->title,
            'page_url' => $seo->canonical,
            'description' => $seo->description,
            'image' => $seo->ogImage,
            'phone' => $this->settings->get('phone'),
            'email' => $this->settings->get('email'),
            'address' => $this->settings->fullAddress(),
        ];
        if ($model instanceof LandingPage) {
            $vars += $model->templateVariables();
        } elseif ($model instanceof Service) {
            $vars['service_name'] = $model->name;
        }

        return $vars;
    }

    /**
     * Render {{vars}} inside admin-supplied JSON-LD with JSON-safe escaping, then decode.
     * Invalid JSON is logged and skipped so a typo never breaks the page or emits broken markup.
     *
     * @return array<int, array>
     */
    public function decodeCustom(string $json, array $vars): array
    {
        $escaped = array_map(fn ($v) => substr(json_encode((string) $v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 1, -1), $vars);
        $rendered = $this->renderer->render($json, $escaped);
        $decoded = json_decode($rendered, true);

        if (! is_array($decoded)) {
            Log::warning('Invalid custom schema JSON skipped', ['error' => json_last_error_msg()]);

            return [];
        }

        $nodes = $decoded['@graph'] ?? (array_is_list($decoded) ? $decoded : [$decoded]);

        return array_values(array_map(function ($node) {
            unset($node['@context']);

            return $node;
        }, array_filter($nodes, 'is_array')));
    }

    /** Same @id → keep the last one (admin overrides win); identical types without @id are kept. */
    private function dedupe(array $graph): array
    {
        $out = [];
        foreach ($graph as $i => $node) {
            $key = $node['@id'] ?? '#node-'.$i;
            $out[$key] = $node;
        }

        return array_values($out);
    }

    public function toJson(array $graph): string
    {
        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => array_values($graph)],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        );
    }

    public static function validateJson(?string $json): ?string
    {
        if ($json === null || trim($json) === '') {
            return null;
        }
        $probe = preg_replace('/\{\{\s*[a-z_]+\s*\}\}/i', 'x', $json);
        json_decode($probe);

        return json_last_error() === JSON_ERROR_NONE ? null : 'Schema JSON is invalid: '.json_last_error_msg();
    }
}
