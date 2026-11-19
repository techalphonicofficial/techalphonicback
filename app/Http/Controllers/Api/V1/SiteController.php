<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\CaseStudy;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Testimonial;
use App\Models\UrlSlug;
use App\Services\BlockPresenter;
use App\Services\FaqService;
use App\Services\InternalLinkService;
use App\Services\SearchService;
use App\Services\SeoService;
use App\Services\SitemapService;
use App\Services\TemplateRenderer;
use App\Services\TocService;
use App\Support\ContentCache;
use App\Support\SitePresenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Read-only "site API" consumed by the Next.js frontend (frontend/). One resolver maps any public path to a
 * typed payload ({kind, seo, data}) built from the SEO, schema, FAQ and internal-link services.
 */
class SiteController extends Controller
{
    private const PREVIEW_TYPES = [
        'page' => Page::class,
        'service' => Service::class,
        'service_category' => ServiceCategory::class,
        'landing_page' => LandingPage::class,
        'blog' => Blog::class,
        'portfolio' => Portfolio::class,
    ];

    public function __construct(
        private SitePresenter $present,
        private BlockPresenter $blocks,
        private SeoService $seo,
        private FaqService $faqs,
        private InternalLinkService $links,
        private TemplateRenderer $template,
        private TocService $toc,
    ) {}

    public function layout(): JsonResponse
    {
        return $this->json(ContentCache::remember('site.layout', fn () => $this->present->layout()));
    }

    /** Every indexable public path (same list as the XML sitemaps); Next prerenders these at build time. */
    public function paths(SitemapService $sitemap): JsonResponse
    {
        $paths = collect(array_keys(SitemapService::SECTIONS))
            ->flatMap(fn ($section) => $sitemap->entries($section)->pluck('loc'))
            ->map(fn ($url) => '/'.trim((string) parse_url($url, PHP_URL_PATH), '/'))
            ->unique()->values();

        return $this->json(['paths' => $paths->all()]);
    }

    /** Active redirect map for the frontend proxy: "/old/" => [target|null, code, id]. */
    public function redirects(): JsonResponse
    {
        return $this->json(['map' => (object) Redirect::activeMap()]);
    }

    public function redirectHit(int $id): JsonResponse
    {
        DB::table('redirects')->where('id', $id)->update(['hits' => DB::raw('hits + 1'), 'last_hit_at' => now()]);

        return $this->json(['ok' => true]);
    }

    public function resolve(Request $request): JsonResponse
    {
        $path = trim((string) parse_url((string) $request->query('path', '/'), PHP_URL_PATH), '/');
        $s = $path === '' ? [] : explode('/', strtolower($path));
        $slug = '[a-z0-9\-]+';
        $is = fn (string $re) => (bool) preg_match('#^'.$re.'$#', implode('/', $s));

        return match (true) {
            $s === [] => $this->home(),
            $is('blog') => $this->blogIndex(),
            $is("blog/category/$slug") => $this->blogCategory($s[2]),
            $is("blog/tag/$slug") => $this->blogTag($s[2]),
            $is("blog/$slug") => $this->model(Blog::published()->where('slug', $s[1])->with(['seo.ogImage', 'seo.twitterImage'])->first()),
            $is('portfolio') => $this->portfolioIndex(),
            $is("portfolio/$slug") => $this->model(Portfolio::published()->where('slug', $s[1])->with(['seo.ogImage', 'featuredImage', 'industry', 'service'])->first()),
            $is('case-studies') => $this->caseStudyIndex(),
            $is("case-studies/$slug") => $this->model(CaseStudy::published()->where('slug', $s[1])->with(['seo.ogImage', 'featuredImage', 'industry', 'service', 'testimonial.photo'])->first()),
            $is('industries') => $this->industries(),
            $is('search') => $this->search($request),
            $is('preview/[a-z_]+/[A-Za-z0-9]{48}') => $this->preview($s[1], explode('/', $path)[2]),
            $is($slug) => $this->slug($s[0]),
            default => $this->notFound(),
        };
    }

    // ------------------------------------------------------------------ home

    private function home(): JsonResponse
    {
        $payload = ContentCache::remember('site.home', function () {
            $sections = HomeSection::where('is_visible', true)->orderBy('sort_order')->get();
            $keys = $sections->pluck('key')->all();
            $has = fn ($k) => in_array($k, $keys, true);

            // The final CTA borrows the promo image when it has none of its own (same as the Blade view).
            $promoImage = HomeSection::where('key', 'promo')->first()?->get('image_id');
            $ids = $sections->flatMap(fn ($sec) => SitePresenter::mediaIds($sec->content ?? []))->push($promoImage)->all();
            $media = $this->present->mediaMap($ids);

            $hero = HeroSlide::where('is_active', true)->with(['image', 'background', 'backgroundVideo'])->orderBy('sort_order')->first();

            $faqSection = $sections->firstWhere('key', 'faq');
            $faqIds = $faqSection ? (array) $faqSection->get('faq_ids', []) : [];
            $faqs = ! $faqSection ? collect() : ($faqIds
                ? Faq::active()->whereIn('id', $faqIds)->get()->sortBy(fn ($f) => array_search($f->id, $faqIds))
                : Faq::active()->whereHas('category', fn ($q) => $q->where('slug', 'general'))->orderBy('sort_order')->limit(6)->get());

            $industryPages = $has('industries') ? LandingPage::published()->serviceIndustry()->orderBy('id')
                ->get(['id', 'slug', 'industry_id', 'status', 'published_at'])->unique('industry_id')->keyBy('industry_id') : collect();

            return [
                'sections' => $sections->map(fn ($sec) => [
                    'key' => $sec->key,
                    'title' => $sec->title,
                    'subtitle' => $sec->subtitle,
                    'eyebrow' => $sec->eyebrow,
                    'content' => $sec->content ?: (object) [],
                ])->values()->all(),
                'media' => (object) $media,
                'promo_image_id' => $promoImage ? (int) $promoImage : null,
                'hero' => $hero ? [
                    'heading' => $hero->heading,
                    'subheading' => $hero->subheading,
                    'description' => $hero->description,
                    'badge' => $hero->badge,
                    'trust_text' => $hero->trust_text,
                    'cta_text' => $hero->cta_text,
                    'cta_url' => $hero->cta_url,
                    'secondary_cta_text' => $hero->secondary_cta_text,
                    'secondary_cta_url' => $hero->secondary_cta_url,
                    'show_form' => (bool) $hero->show_form,
                    'image' => $this->present->media($hero->image),
                    'background' => $this->present->media($hero->background),
                    'background_video' => $this->present->media($hero->backgroundVideo),
                    'showreel_url' => $hero->showreelUrl(),
                ] : null,
                'categories' => $has('services') ? ServiceCategory::published()->orderBy('sort_order')
                    ->with(['services' => fn ($q) => $q->published()->reorder()->orderByDesc('is_featured')->orderBy('sort_order')])->get()
                    ->map(fn ($c) => [
                        'name' => $c->name,
                        'slug' => $c->slug,
                        'menu_label' => $c->menu_label,
                        'heading' => $c->heading,
                        'short_description' => $c->short_description,
                        'icon' => $c->icon,
                        'url' => $c->url,
                        'services_count' => $c->services->count(),
                        'services' => $c->services->take(8)->map(fn ($s) => $this->present->serviceCard($s))->values()->all(),
                    ])->all() : [],
                'industries' => $has('industries') ? Industry::active()->limit(10)->get()
                    ->map(fn ($i) => $this->present->industry($i) + ['url' => $industryPages->get($i->id)?->url])->all() : [],
                'testimonials' => $has('testimonials') ? Testimonial::active()->with('photo')->limit(8)->get()->map(fn ($t) => $this->present->testimonial($t))->all() : [],
                'portfolio' => $has('portfolio') ? Portfolio::published()->with('featuredImage')->orderBy('sort_order')->limit(6)->get()->map(fn ($p) => $this->present->portfolioCard($p))->all() : [],
                'case_studies' => $has('portfolio') ? CaseStudy::published()->with(['featuredImage', 'industry'])->orderBy('sort_order')->limit(3)->get()->map(fn ($c) => $this->present->caseStudyCard($c))->all() : [],
                'blogs' => $has('blogs') ? Blog::published()->with(['featuredImage', 'category'])->latest('published_at')->limit(3)->get()->map(fn ($b) => $this->present->blogCard($b))->all() : [],
                'faqs' => $this->present->faqs($faqs),
                'links' => [
                    'portfolio' => sroute('portfolio.index'),
                    'industries' => sroute('industries.index'),
                    'blog' => sroute('blog.index'),
                    'about' => slash_url('/about-us'),
                ],
            ];
        });

        $seo = $this->seo->make(
            setting('default_meta_title') ?: setting('site_name'),
            setting('default_meta_description'),
            [],
            ['canonical' => slash_url('/'), 'title' => setting('default_meta_title') ?: setting('site_name').' '.setting('title_separator', '|').' '.setting('site_tagline')]
        );
        if ($payload['faqs']) {
            $seo->schema[] = [
                '@type' => 'FAQPage',
                '@id' => slash_url('/').'#faq',
                'mainEntity' => collect($payload['faqs'])->map(fn ($f) => [
                    '@type' => 'Question', 'name' => strip_tags($f['question']),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f['answer'])],
                ])->all(),
            ];
        }

        return $this->page('home', $seo, $payload);
    }

    // ------------------------------------------------------------------ flat /{slug}/ pages

    private function slug(string $slug): JsonResponse
    {
        $entry = Cache::remember('slug:'.$slug, 3600, fn () => UrlSlug::where('slug', $slug)->first(['routable_type', 'routable_id']) ?: false);
        if (! $entry) {
            return $this->notFound();
        }
        $class = Relation::getMorphedModel($entry->routable_type) ?? $entry->routable_type;

        return $this->model(match ($class) {
            Page::class => Page::with(['seo.ogImage', 'seo.twitterImage', 'featuredImage', 'visibleBlocks', 'parent'])->find($entry->routable_id),
            Service::class => Service::with(['seo.ogImage', 'seo.twitterImage', 'heroImage', 'featuredImage', 'visibleBlocks', 'category', 'parent'])->find($entry->routable_id),
            ServiceCategory::class => ServiceCategory::with(['seo.ogImage', 'seo.twitterImage', 'image', 'visibleBlocks'])->find($entry->routable_id),
            LandingPage::class => LandingPage::with(['seo.ogImage', 'seo.twitterImage', 'service.category', 'location', 'industry', 'featuredImage', 'visibleBlocks'])->find($entry->routable_id),
            default => null,
        });
    }

    private function preview(string $type, string $token): JsonResponse
    {
        $class = self::PREVIEW_TYPES[$type] ?? null;
        $model = $class ? $class::withTrashed()->where('preview_token', $token)->first() : null;

        if (! $model || ! $model->previewTokenIsValid($token)) {
            return $this->notFound();
        }

        return $this->model($model, preview: true);
    }

    /** Payload for any CMS model (pages, services, landing pages, posts, portfolio, case studies). */
    private function model(?Model $model, bool $preview = false): JsonResponse
    {
        if (! $model || (! $preview && ! $model->isPublished())) {
            return $this->notFound();
        }

        $common = [
            'faqs' => $this->present->faqs($this->faqs->for($model)),
            'related' => $this->present->linkGroups($this->links->for($model)),
            'is_preview' => $preview,
        ];

        [$kind, $data] = match (true) {
            $model instanceof LandingPage => ['landing', $this->landing($model)],
            $model instanceof Service => ['service', $this->service($model)],
            $model instanceof ServiceCategory => ['category', $this->category($model)],
            $model instanceof Page => ['page', $this->cmsPage($model)],
            $model instanceof Blog => ['blog', $this->blog($model)],
            $model instanceof Portfolio => ['portfolio', $this->portfolio($model)],
            $model instanceof CaseStudy => ['case_study', $this->caseStudy($model)],
        };

        return $this->page($kind, $this->seo->forModel($model, [], $preview), $common + $data);
    }

    private function cmsPage(Page $page): array
    {
        $template = in_array($page->template, ['default', 'packages', 'about', 'contact', 'legal', 'hub'], true) ? $page->template : 'default';

        return [
            'template' => $template,
            'page' => [
                'title' => $page->title,
                'heading' => $page->heading,
                'hero_subheading' => $page->hero_subheading,
                'hero_layout' => $page->hero_layout,
                'excerpt' => $page->excerpt,
                'content' => $page->content,
                'slug' => $page->slug,
                'updated_at' => $page->updated_at?->toIso8601String(),
                'featured_image' => $this->present->media($page->featuredImage),
            ],
            'blocks' => $this->blocks->present($page->visibleBlocks, $page),
            'children' => $template === 'hub'
                ? $page->children()->published()->get()->map(fn ($c) => ['name' => $c->title, 'url' => $c->url, 'description' => $c->excerpt, 'icon' => 'file-text'])->all()
                : [],
            'testimonials' => Testimonial::active()->with('photo')->orderBy('sort_order')->limit(6)->get()->map(fn ($t) => $this->present->testimonial($t))->all(),
            'tagline' => setting('site_tagline'),
            'links' => ['portfolio' => sroute('portfolio.index'), 'contact' => slash_url('/contact')],
        ];
    }

    private function service(Service $service): array
    {
        $blocks = $service->visibleBlocks;
        $technologies = collect($service->technologies ?: [])->map(fn ($t) => is_array($t) ? $t : ['title' => $t])->all();

        $serviceLocations = $service->landingPages()->published()->serviceLocation()->with('location')->get();
        if ($serviceLocations->isNotEmpty()) {
            $locations = $serviceLocations->map(fn ($lp) => [
                'name' => $lp->location?->name ?: $lp->title,
                'country' => $lp->location?->country ?: 'India',
                'state' => $lp->location?->state,
                'type' => $lp->location?->type ?: 'city',
                'url' => $lp->url,
                'title' => $lp->title,
            ])->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        } else {
            $locations = \App\Models\Location::active()->orderBy('name')->get()->map(fn ($l) => [
                'name' => $l->name,
                'country' => $l->country ?: 'India',
                'state' => $l->state,
                'type' => $l->type ?: 'city',
                'url' => url('/location/'.$l->slug.'/'),
                'title' => $service->name.' in '.$l->name,
            ])->values()->all();
        }

        return [
            'service' => [
                'name' => $service->name,
                'heading' => $service->heading,
                'hero_description' => $service->hero_description,
                'hero_layout' => $service->hero_layout,
                'hero_image' => $this->present->media($service->heroImage),
                'hero_highlights' => $service->hero_highlights ?: [],
                'description' => $service->description,
                'content' => $service->content,
                'features' => $service->features ?: [],
                'benefits' => $service->benefits ?: [],
                'process' => $service->process ?: [],
                'technologies' => $technologies,
                'pricing' => $service->pricing ?: [],
                'cta_heading' => $service->cta_heading,
                'cta_description' => $service->cta_description,
                'category' => $service->category ? ['name' => $service->category->name] : null,
            ],
            'locations' => $locations,
            'blocks' => $this->blocks->present($blocks, $service),
            // Default layout only (when no blocks), and only for services with their own published case studies.
            'case_studies' => $blocks->isEmpty() && $service->caseStudies()->published()->exists()
                ? $this->blocks->one('case_studies', ['limit' => 3], $service)['extra']['studies'] : [],
            'testimonials' => Testimonial::active()->with('photo')->orderByRaw('service_id = ? desc', [$service->id])->limit(6)->get()->map(fn ($t) => $this->present->testimonial($t))->all(),
            'blogs' => $this->links->relatedBlogs($service)->map(fn ($b) => $this->present->blogCard($b))->values()->all(),
            'children' => $service->children()->published()->get()->map(fn ($c) => ['name' => $c->menu_label ?: $c->name, 'url' => $c->url])->all(),
        ];
    }

    private function category(ServiceCategory $category): array
    {
        $locations = \App\Models\Location::active()->orderBy('name')->get()->map(fn ($l) => [
            'name' => $l->name,
            'country' => $l->country ?: 'India',
            'state' => $l->state,
            'type' => $l->type ?: 'city',
            'url' => url('/location/'.$l->slug.'/'),
            'title' => $category->name.' in '.$l->name,
        ])->values()->all();

        return [
            'category' => [
                'name' => $category->name,
                'heading' => $category->heading,
                'short_description' => $category->short_description,
                'description' => $category->description,
                'image' => $this->present->media($category->image),
            ],
            'locations' => $locations,
            'services' => $category->services()->published()->whereNull('parent_id')->get()->map(fn ($s) => $this->present->serviceCard($s))->all(),
            'blocks' => $this->blocks->present($category->visibleBlocks, $category),
            'testimonials' => Testimonial::active()->with('photo')->limit(6)->get()->map(fn ($t) => $this->present->testimonial($t))->all(),
        ];
    }

    private function landing(LandingPage $page): array
    {
        $vars = $page->templateVariables();
        $r = fn ($v) => $this->template->render($v, $vars);
        $service = $page->service;

        return [
            'page' => [
                'hero_layout' => $page->hero_layout,
                'faq_enabled' => (bool) $page->faq_enabled,
                'faq_title' => $page->faq_title ? $r($page->faq_title) : null,
                'featured_image' => $this->present->media($page->featuredImage),
            ],
            'service' => $service ? ['name' => $service->name] : null,
            'location' => $page->location ? ['name' => $page->location->name] : null,
            'industry' => $page->industry ? $this->present->industry($page->industry) : null,
            'site_name' => setting('site_name'),
            'h1' => $r($page->heading),
            'subheading' => $r($page->hero_subheading),
            'introduction' => $r($page->introduction),
            'content' => $r($page->content),
            'sections' => $r($page->sections ?? []),
            'benefits' => $r($page->benefits ?: $service?->benefits ?? []),
            'process' => $r($page->process ?: $service?->process ?? []),
            'why_choose_us' => $r($page->why_choose_us ?? []),
            'cta' => $r(array_filter($page->cta ?? []) + ['heading' => $service?->cta_heading, 'text' => $service?->cta_description]),
            'local_facts' => $page->location?->local_facts ?? [],
            'industries' => $page->show_industries && ! $page->industry_id ? $this->blocks->one('industries', ['limit' => 10], $page)['extra']['industries'] : [],
            'testimonials' => $page->show_testimonials ? Testimonial::active()->with('photo')
                ->orderByRaw('service_id = ? desc', [$page->service_id])->limit(6)->get()->map(fn ($t) => $this->present->testimonial($t))->all() : [],
            'blogs' => $this->links->relatedBlogs($page)->map(fn ($b) => $this->present->blogCard($b))->values()->all(),
            'blocks' => $this->blocks->present($page->visibleBlocks, $page, $vars),
        ];
    }

    private function blog(Blog $blog): array
    {
        $blog->loadMissing(['author.avatar', 'category', 'tags', 'featuredImage']);
        $processed = $this->toc->process($blog->content);

        return [
            'blog' => [
                'title' => $blog->title,
                'excerpt' => $blog->excerpt,
                'html' => $processed['html'],
                'toc' => $blog->show_toc && count($processed['toc']) >= 3 ? $processed['toc'] : [],
                'reading_time' => $blog->reading_time,
                'published_at' => $blog->published_at?->toIso8601String(),
                'updated_at' => $blog->updated_at?->toIso8601String(),
                'faq_enabled' => (bool) $blog->faq_enabled,
                'featured_image' => $this->present->media($blog->featuredImage),
                'category' => $blog->category ? ['name' => $blog->category->name, 'url' => $blog->category->url] : null,
                'tags' => $blog->tags->map(fn ($t) => ['name' => $t->name, 'url' => $t->url])->all(),
                'author' => $blog->author ? [
                    'name' => $blog->author->name,
                    'designation' => $blog->author->designation,
                    'bio' => $blog->author->bio,
                    'avatar' => $this->present->media($blog->author->avatar),
                ] : null,
            ],
            'blogs' => $this->links->relatedBlogs($blog)->map(fn ($b) => $this->present->blogCard($b))->values()->all(),
            'contact_url' => slash_url('/contact'),
        ];
    }

    private function portfolio(Portfolio $item): array
    {
        return [
            'item' => $this->present->portfolioCard($item) + [
                'client' => $item->client,
                'description' => $item->description,
                'results' => $item->results,
                'project_url' => $item->project_url,
                'technologies' => $item->technologies ?: [],
                'industry' => $item->industry ? ['name' => $item->industry->name] : null,
                'service' => $item->service ? ['name' => $item->service->name, 'url' => $item->service->url] : null,
                'gallery' => $item->galleryMedia()->map(fn ($m) => $this->present->media($m))->values()->all(),
            ],
            'contact_url' => slash_url('/contact'),
        ];
    }

    private function caseStudy(CaseStudy $study): array
    {
        return [
            'study' => $this->present->caseStudyCard($study) + [
                'challenge' => $study->challenge,
                'solution' => $study->solution,
                'results' => $study->results,
                'technologies' => $study->technologies ?: [],
                'testimonial' => $study->testimonial ? $this->present->testimonial($study->testimonial) : null,
            ],
            'contact_url' => slash_url('/contact'),
        ];
    }

    // ------------------------------------------------------------------ listings

    private function blogIndex(): JsonResponse
    {
        $posts = $this->paginate(Blog::published(), sroute('blog.index'));
        if (! $posts) {
            return $this->notFound();
        }
        $seo = $this->listingSeo('Blog — Insights on SEO, Marketing & Development', 'Practical guides on SEO, digital marketing, web and app development from the '.setting('site_name').' team.', $posts, [['name' => 'Blog', 'url' => null]]);

        return $this->blogListing($seo, $posts, 'Insights & Guides');
    }

    private function blogCategory(string $slug): JsonResponse
    {
        $category = BlogCategory::where('slug', $slug)->first();
        $posts = $category ? $this->paginate(Blog::published()->where('blog_category_id', $category->id), $category->url) : null;
        if (! $posts) {
            return $this->notFound();
        }
        $seo = $this->listingSeo($category->name.' Articles', $category->description, $posts, [
            ['name' => 'Blog', 'url' => sroute('blog.index')], ['name' => $category->name, 'url' => null],
        ]);

        return $this->blogListing($seo, $posts, $category->name, $category->description, $category->id);
    }

    private function blogTag(string $slug): JsonResponse
    {
        $tag = BlogTag::where('slug', $slug)->first();
        $posts = $tag ? $this->paginate(Blog::published()->whereHas('tags', fn ($q) => $q->whereKey($tag->id)), $tag->url) : null;
        if (! $posts) {
            return $this->notFound();
        }
        $seo = $this->listingSeo('Articles tagged “'.$tag->name.'”', null, $posts, [
            ['name' => 'Blog', 'url' => sroute('blog.index')], ['name' => $tag->name, 'url' => null],
        ]);
        $seo->robots = 'noindex, follow';

        return $this->blogListing($seo, $posts, '#'.$tag->name);
    }

    private function blogListing($seo, LengthAwarePaginator $posts, string $heading, ?string $intro = null, ?int $active = null): JsonResponse
    {
        $categories = BlogCategory::withCount(['blogs' => fn ($q) => $q->published()])->orderBy('sort_order')->get()->where('blogs_count', '>', 0);

        return $this->page('blog_index', $seo, [
            'heading' => $heading,
            'intro' => $intro,
            'posts' => collect($posts->items())->map(fn ($b) => $this->present->blogCard($b))->all(),
            'pagination' => [
                'current' => $posts->currentPage(),
                'last' => $posts->lastPage(),
                'base' => $posts->path(),
                'prev' => $posts->previousPageUrl(),
                'next' => $posts->nextPageUrl(),
                'pages' => collect(range(1, $posts->lastPage()))->map(fn ($p) => ['page' => $p, 'url' => $p === 1 ? $posts->path() : $posts->url($p)])->all(),
            ],
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'url' => $c->url, 'count' => $c->blogs_count])->values()->all(),
            'active_category' => $active,
            'blog_url' => sroute('blog.index'),
        ]);
    }

    private function portfolioIndex(): JsonResponse
    {
        $items = Portfolio::published()->with(['featuredImage', 'industry'])->orderBy('sort_order')->get();
        $seo = $this->seo->make('Portfolio — Websites, Apps & Campaigns We\'ve Delivered', 'Explore web development, app and digital marketing projects delivered by '.setting('site_name').'.',
            [['name' => 'Home', 'url' => slash_url('/')], ['name' => 'Portfolio', 'url' => null]],
            ['canonical' => sroute('portfolio.index'), 'schema_type' => 'CollectionPage', 'items' => $items->map(fn ($i) => ['url' => $i->url, 'name' => $i->title])->all()]);

        return $this->page('portfolio_index', $seo, [
            'items' => $items->map(fn ($p) => $this->present->portfolioCard($p))->all(),
            'categories' => $items->pluck('category')->filter()->unique()->values()->all(),
        ]);
    }

    private function caseStudyIndex(): JsonResponse
    {
        $studies = CaseStudy::published()->with(['featuredImage', 'industry', 'service'])->orderBy('sort_order')->get();
        $seo = $this->seo->make('Case Studies — Real Results for Real Businesses', 'How '.setting('site_name').' helped clients grow traffic, leads and revenue.',
            [['name' => 'Home', 'url' => slash_url('/')], ['name' => 'Case Studies', 'url' => null]],
            ['canonical' => sroute('case-studies.index'), 'schema_type' => 'CollectionPage', 'items' => $studies->map(fn ($i) => ['url' => $i->url, 'name' => $i->title])->all()]);

        return $this->page('case_study_index', $seo, ['studies' => $studies->map(fn ($c) => $this->present->caseStudyCard($c))->all()]);
    }

    private function industries(): JsonResponse
    {
        $industries = Industry::active()->with('image')->get();
        $pages = LandingPage::published()->serviceIndustry()->with('service')->get()->groupBy('industry_id');
        $seo = $this->seo->make('Industries We Serve', 'Industry-specific SEO, marketing and development for '.$industries->take(5)->pluck('name')->implode(', ').' and more.',
            [['name' => 'Home', 'url' => slash_url('/')], ['name' => 'Industries', 'url' => null]], ['canonical' => sroute('industries.index'), 'schema_type' => 'CollectionPage']);

        return $this->page('industries', $seo, [
            'industries' => $industries->map(fn ($i) => $this->present->industry($i) + [
                'pages' => ($pages[$i->id] ?? collect())->map(fn ($p) => ['name' => ($p->service?->name).' for '.$i->name, 'url' => $p->url])->values()->all(),
            ])->all(),
        ]);
    }

    private function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $seo = $this->seo->make($q ? 'Search results for “'.$q.'”' : 'Search', null, [], ['robots' => 'noindex, follow', 'canonical' => sroute('search')]);

        return $this->page('search', $seo, ['q' => $q, 'results' => app(SearchService::class)->search($q)->all()]);
    }

    // ------------------------------------------------------------------ helpers

    private function paginate($query, string $path): ?LengthAwarePaginator
    {
        $posts = $query->with(['featuredImage', 'category', 'author'])->latest('published_at')->paginate(9)->withPath($path)->onEachSide(1);

        return $posts->currentPage() > 1 && $posts->isEmpty() ? null : $posts;
    }

    private function listingSeo(string $title, ?string $description, LengthAwarePaginator $posts, array $crumbs)
    {
        $page = $posts->currentPage();
        $base = $posts->path();

        return $this->seo->make(
            $title.($page > 1 ? ' — Page '.$page : ''),
            $description,
            array_merge([['name' => 'Home', 'url' => slash_url('/')]], $crumbs),
            [
                'canonical' => $page > 1 ? $base.'?page='.$page : $base,
                'prevUrl' => $posts->previousPageUrl(),
                'nextUrl' => $posts->nextPageUrl(),
                'schema_type' => 'CollectionPage',
                'items' => $posts->map(fn ($p) => ['url' => $p->url, 'name' => $p->title])->all(),
            ]
        );
    }

    private function page(string $kind, $seo, array $data): JsonResponse
    {
        return $this->json(['kind' => $kind, 'seo' => $this->present->seo($seo), 'data' => $data]);
    }

    private function notFound(): JsonResponse
    {
        return $this->json(['kind' => 'not_found'], 404);
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        // Unescaped slashes so the frontend can rewrite the backend origin with a plain string replace.
        return response()->json($data, $status, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
