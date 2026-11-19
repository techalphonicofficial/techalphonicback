<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\CaseStudy;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\ContentCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Database-driven sitemaps. Only published + indexable + self-canonical URLs are listed.
 */
class SitemapService
{
    /** Sitemap name => [model class, priority, changefreq] */
    public const SECTIONS = [
        'pages' => [[Page::class, ServiceCategory::class], 0.8, 'monthly'],
        'services' => [[Service::class], 0.9, 'monthly'],
        'locations' => [[LandingPage::class], 0.7, 'monthly'],
        'industries' => [[LandingPage::class], 0.7, 'monthly'],
        'blog' => [[Blog::class], 0.6, 'weekly'],
        'portfolio' => [[Portfolio::class, CaseStudy::class], 0.5, 'monthly'],
    ];

    public const CHUNK = 5000;

    /** @return array<string, ?Carbon> section => lastmod */
    public function index(): array
    {
        return ContentCache::remember('sitemap.index', function () {
            $out = [];
            foreach (array_keys(self::SECTIONS) as $section) {
                $entries = $this->entries($section);
                if ($entries->isNotEmpty()) {
                    $out[$section] = $entries->max('lastmod');
                }
            }

            return $out;
        }, 3600);
    }

    /** @return Collection<int, array{loc: string, lastmod: ?Carbon, priority: float, changefreq: string, images: array}> */
    public function entries(string $section): Collection
    {
        abort_unless(isset(self::SECTIONS[$section]), 404);

        return ContentCache::remember("sitemap.$section", function () use ($section) {
            [$classes, $priority, $freq] = self::SECTIONS[$section];
            $entries = collect();

            if ($section === 'pages') {
                $entries->push(['loc' => slash_url('/'), 'lastmod' => now()->startOfDay(), 'priority' => 1.0, 'changefreq' => 'weekly', 'images' => []]);
                foreach (['blog.index', 'portfolio.index', 'case-studies.index', 'industries.index'] as $route) {
                    $entries->push(['loc' => sroute($route), 'lastmod' => null, 'priority' => 0.6, 'changefreq' => 'weekly', 'images' => []]);
                }
            }

            foreach ($classes as $class) {
                $query = $class::query()->published()->with('seo');
                if (method_exists($class, 'featuredImage')) {
                    $query->with('featuredImage');
                }

                if ($class === LandingPage::class) {
                    $section === 'industries'
                        ? $query->whereNotNull('industry_id')
                        : $query->whereNull('industry_id');
                }

                $this->indexable($query)->orderBy('id')->chunk(500, function ($rows) use (&$entries, $priority, $freq) {
                    foreach ($rows as $row) {
                        $canonical = $row->seo?->canonical_url;
                        if ($canonical && rtrim(url($canonical), '/') !== rtrim($row->url, '/')) {
                            continue; // canonicalised elsewhere — never list duplicates
                        }
                        $img = $row->relationLoaded('featuredImage') ? $row->featuredImage : null;
                        $entries->push([
                            'loc' => $row->url,
                            'lastmod' => $row->updated_at,
                            'priority' => $row->seo?->sitemap_priority ?? $priority,
                            'changefreq' => $row->seo?->sitemap_changefreq ?? $freq,
                            'images' => $img ? [['loc' => $img->url, 'title' => $img->title ?: $img->alt]] : [],
                        ]);
                    }
                });
            }

            return $entries;
        }, 3600);
    }

    private function indexable(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->doesntHave('seo')->orWhereHas('seo', fn ($s) => $s
            ->where('robots_index', true)->where('include_in_sitemap', true)));
    }
}
