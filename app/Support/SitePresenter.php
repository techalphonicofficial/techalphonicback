<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\Media;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\SocialLink;
use App\Models\Testimonial;
use App\Services\MenuService;
use App\Services\SchemaService;
use App\Services\SettingsService;
use Illuminate\Support\Collection;

/**
 * Turns models into the plain arrays the Next.js frontend renders (the "site API").
 * Shapes mirror exactly what the Blade views read, so both frontends show the same content.
 */
class SitePresenter
{
    /** Settings the public site may see. Secrets (notification emails, robots, etc.) stay server-side. */
    private const PUBLIC_SETTINGS = [
        'site_name', 'site_tagline', 'primary_color', 'secondary_color', 'accent_color',
        'phone', 'phone_secondary', 'email', 'address_line', 'address_locality', 'address_region', 'postal_code',
        'country', 'map_embed_url', 'business_hours',
        'whatsapp_enabled', 'whatsapp_position', 'call_enabled', 'call_number', 'call_text',
        'default_meta_title', 'title_separator', 'twitter_handle', 'discourage_indexing',
        'ga4_id', 'gtm_id', 'gsc_verification', 'meta_pixel_id', 'head_scripts', 'body_scripts',
        'lead_success_message', 'recaptcha_enabled', 'budget_options',
        'popup_enabled', 'popup_delay', 'popup_badge', 'popup_title', 'popup_text', 'popup_points', 'quote_tab_enabled',
        'footer_about', 'footer_cta_heading', 'footer_cta_text',
    ];

    private const MENUS = ['main', 'mobile', 'footer_company', 'footer_services', 'footer_development', 'footer_seo',
        'footer_industries', 'footer_resources', 'legal'];

    public function __construct(private SettingsService $settings, private MenuService $menus) {}

    // ---------------------------------------------------------------- media

    public function media(?Media $m): ?array
    {
        if (! $m) {
            return null;
        }

        return [
            'id' => $m->id,
            'url' => $m->url,
            'webp' => $m->webp_url,
            'avif' => isset($m->variants['avif']) ? $m->urlFor($m->variants['avif']) : null,
            'srcset' => $m->srcset(),
            'width' => $m->width,
            'height' => $m->height,
            'alt' => $m->alt,
            'title' => $m->title,
            'caption' => $m->caption,
            'mime' => $m->mime_type,
        ];
    }

    /** id => media for every id given (one query). */
    public function mediaMap(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (! $ids) {
            return [];
        }

        return Media::whereIn('id', $ids)->get()->mapWithKeys(fn ($m) => [$m->id => $this->media($m)])->all();
    }

    /** Collect ids stored under media keys anywhere in a nested content array. */
    public static function mediaIds(mixed $data): array
    {
        $ids = [];
        if (is_array($data)) {
            array_walk_recursive($data, function ($v, $k) use (&$ids) {
                if (in_array($k, ['image_id', 'logo_id', 'photo_id', 'video_id'], true) && $v) {
                    $ids[] = (int) $v;
                }
            });
            foreach ((array) ($data['gallery_ids'] ?? []) as $g) {
                $ids[] = (int) $g;
            }
        }

        return $ids;
    }

    // ---------------------------------------------------------------- layout

    public function layout(): array
    {
        $s = $this->settings;
        $settings = collect(self::PUBLIC_SETTINGS)->mapWithKeys(fn ($k) => [$k => $s->get($k)])->all();
        foreach (['whatsapp_enabled', 'call_enabled', 'discourage_indexing', 'recaptcha_enabled', 'popup_enabled', 'quote_tab_enabled'] as $b) {
            $settings[$b] = $s->bool($b);
        }
        $phone = $s->get('phone');
        $callNumber = $s->get('call_number') ?: $phone;

        return [
            'settings' => $settings + [
                'logo' => $this->media($s->media('logo')),
                'logo_dark' => $this->media($s->media('logo_dark')),
                'favicon' => $this->media($s->media('favicon')),
                'popup_image' => $this->media($s->media('popup_image')),
                'whatsapp_url' => $s->whatsappUrl(),
                'phone_href' => $phone ? $s->telHref($phone) : null,
                'call_href' => $callNumber ? $s->telHref($callNumber) : null,
                'full_address' => $s->fullAddress() ?: null,
                'copyright' => $s->replaceTokens((string) $s->get('footer_copyright')),
                'recaptcha_site_key' => $s->bool('recaptcha_enabled') ? config('services.recaptcha.key') : null,
                'form_services' => ContentCache::remember('form.services', fn () => Service::published()->whereNull('parent_id')->orderBy('sort_order')->pluck('name')->all()),
            ],
            'menus' => collect(self::MENUS)->mapWithKeys(fn ($loc) => [$loc => $this->menus->tree($loc)])->all(),
            'socials' => ContentCache::remember('social.links', fn () => SocialLink::active()->get())
                ->map(fn ($l) => ['platform' => $l->platform, 'url' => $l->url, 'icon' => $l->icon ?: strtolower($l->platform)])->values()->all(),
            'popular_services' => ContentCache::remember('404.popular', fn () => Service::published()->where('is_featured', true)->orderBy('sort_order')->limit(6)->get())
                ->map(fn ($s) => $this->serviceCard($s))->values()->all(),
            'home_url' => slash_url('/'),
        ];
    }

    // ---------------------------------------------------------------- seo

    public function seo(SeoData $seo): array
    {
        return [
            'title' => $seo->title,
            'description' => $seo->description,
            'keywords' => $seo->keywords,
            'canonical' => $seo->canonical,
            'robots' => $seo->robots,
            'indexable' => $seo->isIndexable(),
            'og_title' => $seo->ogTitle ?? $seo->title,
            'og_description' => $seo->ogDescription,
            'og_image' => $seo->ogImage,
            'og_type' => $seo->ogType,
            'twitter_title' => $seo->twitterTitle ?? $seo->title,
            'twitter_description' => $seo->twitterDescription,
            'twitter_image' => $seo->twitterImage,
            'prev_url' => $seo->prevUrl,
            'next_url' => $seo->nextUrl,
            'published_time' => $seo->publishedTime,
            'modified_time' => $seo->modifiedTime,
            'alternates' => $seo->alternates ?: (object) [],
            'breadcrumbs' => $seo->breadcrumbs,
            'schema_json' => $seo->schema ? app(SchemaService::class)->toJson($seo->schema) : null,
        ];
    }

    // ---------------------------------------------------------------- content shapes

    public function serviceCard(Service $s): array
    {
        return ['name' => $s->menu_label ?: $s->name, 'url' => $s->url, 'description' => $s->short_description, 'icon' => $s->icon];
    }

    public function blogCard(Blog $b): array
    {
        return [
            'title' => $b->title,
            'url' => $b->url,
            'excerpt' => $b->excerpt,
            'reading_time' => $b->reading_time,
            'published_at' => $b->published_at?->toIso8601String(),
            'featured_image' => $this->media($b->featuredImage),
            'category' => $b->category ? ['name' => $b->category->name, 'url' => $b->category->url] : null,
        ];
    }

    public function testimonial(Testimonial $t): array
    {
        return [
            'name' => $t->name,
            'designation' => $t->designation,
            'company' => $t->company,
            'rating' => (int) $t->rating,
            'review' => $t->review,
            'initials' => $t->initials(),
            'photo' => $this->media($t->photo),
        ];
    }

    public function industry(Industry $i): array
    {
        return [
            'id' => $i->id,
            'name' => $i->name,
            'slug' => $i->slug,
            'plural_name' => $i->plural_name,
            'description' => $i->description,
            'icon' => $i->icon,
            'challenges' => $i->challenges ?: [],
            'solutions' => $i->solutions ?: [],
        ];
    }

    public function caseStudyCard(CaseStudy $c): array
    {
        return [
            'title' => $c->title,
            'url' => $c->url,
            'client' => $c->client,
            'summary' => $c->summary,
            'metrics' => $c->metrics ?: [],
            'featured_image' => $this->media($c->featuredImage),
            'industry' => $c->relationLoaded('industry') && $c->industry ? ['name' => $c->industry->name] : null,
            'service' => $c->relationLoaded('service') && $c->service ? ['name' => $c->service->name, 'url' => $c->service->url] : null,
        ];
    }

    public function portfolioCard(Portfolio $p): array
    {
        return [
            'title' => $p->title,
            'url' => $p->url,
            'category' => $p->category,
            'summary' => $p->summary,
            'featured_image' => $this->media($p->featuredImage),
        ];
    }

    public function faqs(Collection $faqs): array
    {
        return $faqs->map(fn ($f) => ['id' => $f->id ?? null, 'question' => $f->question, 'answer' => $f->answer])->values()->all();
    }

    /** InternalLinkService groups → [{title, links: [...]}] (keeps order). */
    public function linkGroups(array $groups): array
    {
        return collect($groups)->map(fn ($links, $title) => ['title' => $title, 'links' => collect($links)->values()->all()])->values()->all();
    }
}
