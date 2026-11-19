<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Models\Author;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Faq;
use App\Models\LandingPage;
use App\Models\Location;
use App\Models\Media;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\SitemapImport;
use App\Models\UrlSlug;
use App\Services\MediaService;
use App\Support\ContentCache;
use App\Support\LegacyHtml;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * One-off migration from the old WordPress site (www.techalphonic.com):
 *  1. blog posts + categories + featured images (→ /blog/{slug}/)
 *  2. Elementor pages that have no equivalent yet (kept on their original URL)
 *  3. slug alignment for existing pages so ranking URLs stay identical
 *  4. 301s for every other URL in the old XML sitemap (feeds, pagination, categories, posts)
 * Safe to re-run: everything is matched by slug.
 */
class ImportLegacySite extends Command
{
    protected $signature = 'legacy:import
        {--base=https://www.techalphonic.com : Old site URL}
        {--sitemap=database/legacy/sitemap.xml : Old XML sitemap (relative to the project root)}
        {--skip-posts : Do not import blog posts}
        {--skip-pages : Do not import pages}';

    protected $description = 'Import blog posts and pages from the old WordPress site and create 301s from the old sitemap';

    /**
     * Old page slug => where it lives in the new CMS. Everything keeps its original URL.
     * service: [parent service slug]; landing: [service slug, location slug]; page: default template.
     */
    private const PAGES = [
        // Mobile app types → children of App Development
        'social-media-apps' => ['service', 'app-development', 'smartphone'],
        'gaming-apps' => ['service', 'app-development', 'smartphone'],
        'productivity-apps' => ['service', 'app-development', 'smartphone'],
        'lifestyle-and-fitness-apps' => ['service', 'app-development', 'smartphone'],
        'e-commerce-apps' => ['service', 'app-development', 'smartphone'],
        'educational-apps' => ['service', 'app-development', 'smartphone'],
        'entertainment-apps' => ['service', 'app-development', 'smartphone'],
        // Web application types → children of Custom Web Development
        'static-web-apps' => ['service', 'custom-web-development', 'code'],
        'content-management-systems-cms' => ['service', 'custom-web-development', 'layout'],
        'e-commerce-web-apps' => ['service', 'custom-web-development', 'code'],
        'single-page-applications-spa' => ['service', 'custom-web-development', 'code'],
        'multi-page-applications-mpa' => ['service', 'custom-web-development', 'code'],
        'booking-and-reservation-apps' => ['service', 'custom-web-development', 'code'],
        'finance-and-banking-apps' => ['service', 'custom-web-development', 'code'],
        'productivity-software' => ['service', 'custom-web-development', 'code'],
        'media-and-entertainment' => ['service', 'custom-web-development', 'code'],
        'ayurveda-clinical-management-software' => ['service', 'custom-web-development', 'code'],
        // Location pages
        'digital-marketing-company-in-noida' => ['page'],
        'digital-marketing-company-in-india' => ['page'],
        'digital-marketing-company-in-ghaziabad' => ['page'],
        'ai-seo-company-in-india' => ['landing', 'ai-seo-services', 'india'],
        'seo-company-in-los-angeles' => ['landing', 'search-engine-optimization', 'los-angeles'],
        'career' => ['page'],
    ];

    /** New-site slugs renamed to the old ranking URL. */
    private const RENAMES = [
        [Service::class, 'ai-seo', 'ai-seo-services'],
        [Service::class, 'off-page-seo', 'off-page-seo-services'],
        [Page::class, 'terms-and-conditions', 'terms-condition'],
    ];

    private LegacyHtml $html;

    private array $mediaCache = [];

    public function handle(MediaService $media): int
    {
        $this->html = new LegacyHtml(parse_url($this->option('base'), PHP_URL_HOST) ?: 'techalphonic.com');

        $this->renameSlugs();
        if (! $this->option('skip-posts')) {
            $this->importPosts($media);
        }
        if (! $this->option('skip-pages')) {
            $this->importPages($media);
        }
        $this->mapSitemap();

        ContentCache::flush();
        $this->info('Done.');

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------- slugs

    private function renameSlugs(): void
    {
        foreach (self::RENAMES as [$class, $from, $to]) {
            $model = $class::where('slug', $from)->first();
            if ($model && ! $class::where('slug', $to)->exists()) {
                $model->update(['slug' => $to]); // HasSlugRoute records the /from/ → /to/ redirect
                $this->line("Renamed /$from/ → /$to/");
            }
        }
    }

    // ---------------------------------------------------------------- posts

    private function importPosts(MediaService $media): void
    {
        $categories = collect($this->api('categories', ['per_page' => 100]))
            ->filter(fn ($c) => $c['count'] > 0 && $c['slug'] !== 'uncategorized');
        $catMap = [];
        foreach ($categories as $i => $c) {
            $catMap[$c['id']] = BlogCategory::updateOrCreate(['slug' => $c['slug']], [
                'name' => html_entity_decode($c['name'], ENT_QUOTES | ENT_HTML5), 'sort_order' => $i,
            ])->id;
        }

        $authorId = Author::orderBy('id')->value('id');
        $posts = $this->apiAll('posts', ['_embed' => 'wp:featuredmedia']);
        $slugs = [];
        $bar = $this->output->createProgressBar(count($posts));
        $this->line('Importing '.count($posts).' blog posts…');

        foreach ($posts as $p) {
            $slugs[] = $p['slug'];
            $content = $this->html->post($p['content']['rendered']);
            $words = str_word_count(strip_tags($content));
            $featured = $p['_embedded']['wp:featuredmedia'][0] ?? null;
            $imageId = $featured ? $this->importImage($media, $featured['source_url'], $featured['alt_text'] ?: html_entity_decode($p['title']['rendered']), 'blog') : null;
            $excerpt = trim(html_entity_decode(strip_tags($p['excerpt']['rendered']), ENT_QUOTES | ENT_HTML5));
            $excerpt = Str::limit(preg_replace('/\s*\[(…|&hellip;|\.\.\.)\]\s*$/u', '', $excerpt), 300);

            $blog = Blog::withTrashed()->updateOrCreate(['slug' => $p['slug']], [
                'title' => html_entity_decode($p['title']['rendered'], ENT_QUOTES | ENT_HTML5),
                'excerpt' => $excerpt,
                'content' => $content,
                'blog_category_id' => $catMap[$p['categories'][0] ?? 0] ?? null,
                'author_id' => $authorId,
                'featured_image_id' => $imageId,
                'reading_time' => max(1, (int) ceil($words / 220)),
                'show_toc' => true,
                'status' => ContentStatus::Published,
                'published_at' => $p['date'],
            ]);
            if ($blog->trashed()) {
                $blog->restore();
            }
            $blog->syncSeo($this->liveMeta($p['link']));
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // Remove the demo posts that shipped with the starter content.
        $removed = Blog::whereNotIn('slug', $slugs)->get();
        foreach ($removed as $b) {
            $b->forceDelete();
        }
        $this->line('Removed '.$removed->count().' demo post(s).');
    }

    // ---------------------------------------------------------------- pages

    private function importPages(MediaService $media): void
    {
        $pages = collect($this->apiAll('pages', ['_fields' => 'id,slug,link,title,content']))->keyBy('slug');
        Location::firstOrCreate(['slug' => 'los-angeles'], [
            'name' => 'Los Angeles', 'type' => 'city', 'state' => 'California', 'country' => 'United States',
            'status' => 'published', 'sort_order' => 100,
        ]);

        foreach (self::PAGES as $slug => $target) {
            $wp = $pages[$slug] ?? null;
            if (! $wp) {
                $this->warn("Not found on the old site: /$slug/");

                continue;
            }
            $data = $this->html->elementor($wp['content']['rendered']);
            $name = $this->fixCase(html_entity_decode($wp['title']['rendered'], ENT_QUOTES | ENT_HTML5));
            $h1 = $this->fixCase($data['title'] ?: $name);
            $imageId = isset($data['images'][0]) ? $this->importImage($media, $data['images'][0], $h1, 'pages') : null;
            $publish = ['status' => ContentStatus::Published, 'published_at' => now()];

            $model = match ($target[0]) {
                'service' => $this->importService($slug, $name, $h1, $data, $imageId, $target, $publish),
                'landing' => $this->importLanding($slug, $name, $h1, $data, $imageId, $target, $publish),
                default => Page::withTrashed()->updateOrCreate(['slug' => $slug], $publish + [
                    'title' => $name, 'h1' => $h1, 'template' => 'default', 'hero_layout' => 'split',
                    'excerpt' => $data['intro'], 'content' => $data['content'], 'featured_image_id' => $imageId,
                ]),
            };
            if (! $model) {
                continue;
            }
            if ($model->trashed()) {
                $model->restore();
            }
            $model->syncSeo($this->liveMeta($wp['link']));
            if ($data['faqs'] && method_exists($model, 'syncFaqs')) {
                $model->syncFaqs($this->storeFaqs($data['faqs']));
            }
            $this->line("Imported /$slug/ as ".class_basename($model));
        }
    }

    private function importService(string $slug, string $name, string $h1, array $data, ?int $imageId, array $target, array $publish): Service
    {
        $parent = Service::where('slug', $target[1])->firstOrFail();

        return Service::withTrashed()->updateOrCreate(['slug' => $slug], $publish + [
            'parent_id' => $parent->id,
            'service_category_id' => $parent->service_category_id,
            'name' => $name,
            'menu_label' => Str::limit($name, 40, ''),
            'icon' => $target[2] ?? $parent->icon,
            'hero_heading' => $h1,
            'hero_description' => $data['intro'],
            'short_description' => Str::limit((string) $data['intro'], 160),
            'content' => $data['content'],
            'process' => $data['process'] ?: null,
            'featured_image_id' => $imageId,
            'hero_layout' => 'split',
            'sort_order' => 100 + array_search($slug, array_keys(self::PAGES), true),
        ]);
    }

    private function importLanding(string $slug, string $name, string $h1, array $data, ?int $imageId, array $target, array $publish): ?LandingPage
    {
        $service = Service::where('slug', $target[1])->first();
        $location = Location::where('slug', $target[2])->first();
        if (! $service || ! $location) {
            $this->warn("Skipped /$slug/ — service or location missing");

            return null;
        }
        $existing = LandingPage::withTrashed()->where('slug', $slug)->first()
            ?? LandingPage::withTrashed()->where(['service_id' => $service->id, 'location_id' => $location->id, 'industry_id' => null])->first();
        $attrs = $publish + [
            'page_type' => 'service_location', 'service_id' => $service->id, 'location_id' => $location->id,
            'title' => $name, 'slug' => $slug, 'h1' => $h1, 'hero_layout' => 'split',
            'introduction' => $data['intro'], 'content' => $data['content'],
            'process' => $data['process'] ?: null, 'featured_image_id' => $imageId,
            'generated_by' => 'manual',
        ];

        return $existing ? tap($existing)->update($attrs) : LandingPage::create($attrs);
    }

    /** @return int[] */
    private function storeFaqs(array $faqs): array
    {
        $ids = [];
        foreach ($faqs as $i => $f) {
            $ids[] = Faq::updateOrCreate(['question' => Str::limit($f['q'], 250, '')], ['answer' => $f['a'], 'is_active' => true, 'sort_order' => $i])->id;
        }

        return $ids;
    }

    // ---------------------------------------------------------------- sitemap → redirects

    private function mapSitemap(): void
    {
        $file = base_path($this->option('sitemap'));
        if (! is_file($file)) {
            $this->warn("Sitemap not found at $file — skipping redirects.");

            return;
        }
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);
        if (! $xml) {
            $this->error('Could not parse the sitemap.');

            return;
        }

        $counts = ['exists' => 0, 'redirect' => 0, 'unmapped' => 0];
        foreach ($xml->url as $u) {
            $path = Redirect::normalizePath((string) $u->loc);
            $target = $this->targetFor($path);
            $status = $target === $path ? 'mapped' : ($target ? 'redirected' : 'unmapped');

            SitemapImport::updateOrCreate(['old_url' => $path], [
                'new_url' => $target, 'status' => $status,
                'lastmod' => isset($u->lastmod) ? rescue(fn () => now()->parse((string) $u->lastmod), null, false) : null,
                'page_type' => $this->typeFor($path),
            ]);
            if ($status === 'redirected') {
                Redirect::updateOrCreate(['old_url' => $path], ['new_url' => $target, 'status_code' => 301, 'is_active' => true, 'source' => 'import']);
                $counts['redirect']++;
            } elseif ($status === 'mapped') {
                // A URL that exists again must not keep an old redirect pointing away from it.
                Redirect::where('old_url', $path)->delete();
                $counts['exists']++;
            } else {
                $counts['unmapped']++;
                $this->warn("No target for $path");
            }
        }
        \Illuminate\Support\Facades\Cache::forget(Redirect::CACHE_KEY);
        $this->info("Sitemap: {$counts['exists']} URLs live on the same address, {$counts['redirect']} redirected (301), {$counts['unmapped']} unmapped.");
    }

    /** New URL for an old path; same path when it already exists. */
    private function targetFor(string $path): ?string
    {
        if ($path === '/' || $this->resolves($path)) {
            return $path;
        }
        if (preg_match('#^(.*/)feed/$#', $path, $m)) { // RSS feeds → their page
            $parent = $m[1];
            if ($parent === '/' || str_starts_with($parent, '/comments/') || str_starts_with($parent, '/author/')) {
                return '/blog/';
            }

            return $this->targetFor($parent) ?? '/blog/';
        }
        if (preg_match('#^/blog/page/\d+/$#', $path) || str_starts_with($path, '/author/')) {
            return '/blog/';
        }
        if (preg_match('#^/category/([a-z0-9-]+)/$#', $path, $m)) {
            return BlogCategory::where('slug', $m[1])->exists() ? '/blog/category/'.$m[1].'/' : '/blog/';
        }
        if ($path === '/our-work/') {
            return '/portfolio/';
        }
        $slug = trim($path, '/');
        if (! str_contains($slug, '/') && Blog::where('slug', $slug)->exists()) {
            return '/blog/'.$slug.'/';
        }
        foreach (self::RENAMES as [, $from, $to]) {
            if ($slug === $from) {
                return '/'.$to.'/';
            }
        }

        return null;
    }

    private function resolves(string $path): bool
    {
        $slug = trim($path, '/');
        if (! str_contains($slug, '/') && UrlSlug::where('slug', $slug)->exists()) {
            return true;
        }

        return (bool) preg_match('#^/(blog|portfolio|case-studies|industries)/$#', $path);
    }

    private function typeFor(string $path): string
    {
        return match (true) {
            str_ends_with($path, '/feed/') => 'feed',
            str_starts_with($path, '/blog/page/') => 'pagination',
            str_starts_with($path, '/category/') => 'category',
            Blog::where('slug', trim($path, '/'))->exists() => 'blog',
            default => 'page',
        };
    }

    // ---------------------------------------------------------------- helpers

    private function api(string $endpoint, array $query = []): array
    {
        $resp = Http::withUserAgent('TechAlphonic-Migration/1.0')->timeout(60)->retry(2, 1000)
            ->get(rtrim($this->option('base'), '/').'/wp-json/wp/v2/'.$endpoint, $query);
        $resp->throw();

        return $resp->json() ?? [];
    }

    private function apiAll(string $endpoint, array $query = []): array
    {
        $all = [];
        for ($page = 1; $page < 50; $page++) {
            $resp = Http::withUserAgent('TechAlphonic-Migration/1.0')->timeout(90)->retry(2, 1000)
                ->get(rtrim($this->option('base'), '/').'/wp-json/wp/v2/'.$endpoint, $query + ['per_page' => 100, 'page' => $page]);
            $resp->throw();
            $all = array_merge($all, $resp->json() ?? []);
            if ($page >= (int) $resp->header('X-WP-TotalPages')) {
                break;
            }
        }

        return $all;
    }

    /** Meta title / description exactly as the old site served them (Rank Math / Yoast agnostic). */
    private function liveMeta(string $url): array
    {
        $html = rescue(fn () => Http::withUserAgent('TechAlphonic-Migration/1.0')->timeout(45)->get($url)->body(), '', false);
        $meta = [];
        if (preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m)) {
            $title = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5));
            // WordPress' default "Page – Site Name" titles are weaker than the new SEO templates, so only keep custom ones.
            $meta['meta_title'] = preg_match('/\s[-–|]\s*Tech Alphonic( Pvt\.? Ltd\.?)?$/i', $title) ? null : Str::limit($title, 250, '');
        }
        if (preg_match('#<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)#i', $html, $m)
            || preg_match('#<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\']#i', $html, $m)) {
            $meta['meta_description'] = Str::limit(trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5)), 320, '');
        }

        return array_filter($meta, fn ($v) => $v !== ''); // null clears a stale title
    }

    /** "SEO COMPANY IN LOS ANGELES" → "SEO Company In Los Angeles" (acronyms kept). */
    private function fixCase(string $text): string
    {
        if (mb_strtoupper($text) !== $text) {
            return $text;
        }

        return preg_replace_callback('/\b([A-Z])([A-Z]+)\b/u', fn ($m) => in_array($m[0], ['SEO', 'AI', 'UI', 'UX', 'PPC', 'SMO', 'ORM', 'CMS', 'SPA', 'MPA', 'IT', 'USA', 'UK'], true) ? $m[0] : $m[1].mb_strtolower($m[2]), $text);
    }

    private function importImage(MediaService $media, string $url, string $alt, string $folder): ?int
    {
        $url = preg_replace('#^https?://techalphonic\.com#', 'https://www.techalphonic.com', $url);
        $name = basename(parse_url($url, PHP_URL_PATH));
        if (isset($this->mediaCache[$name])) {
            return $this->mediaCache[$name];
        }
        if ($id = Media::where('original_name', $name)->value('id')) {
            return $this->mediaCache[$name] = $id;
        }
        $resp = rescue(fn () => Http::withUserAgent('TechAlphonic-Migration/1.0')->timeout(60)->get($url), null, false);
        if (! $resp || ! $resp->successful() || ! str_starts_with((string) $resp->header('Content-Type'), 'image/')) {
            $this->warn("Image not downloaded: $url");

            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'lg');
        file_put_contents($tmp, $resp->body());
        try {
            $file = new UploadedFile($tmp, $name, mime_content_type($tmp) ?: null, null, true);
            $id = $media->store($file, ['alt' => Str::limit($alt, 200, ''), 'title' => Str::limit($alt, 200, ''), 'folder' => $folder])->id;
        } finally {
            @unlink($tmp);
        }

        return $this->mediaCache[$name] = $id;
    }
}
