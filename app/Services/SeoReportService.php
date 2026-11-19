<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Blog;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Site-wide SEO health counts for the dashboard. Cached briefly — these are aggregate COUNT queries.
 */
class SeoReportService
{
    public const MODELS = [
        'Pages' => Page::class,
        'Services' => Service::class,
        'SEO pages' => LandingPage::class,
        'Blogs' => Blog::class,
    ];

    public function summary(): array
    {
        return Cache::remember('admin.seo-report', 300, function () {
            $rows = [];
            foreach (self::MODELS as $label => $class) {
                $base = fn (): Builder => $class::query();
                $rows[$label] = [
                    'missing_title' => $base()->where(fn ($q) => $q->doesntHave('seo')->orWhereHas('seo', fn ($s) => $s->whereNull('meta_title')->orWhere('meta_title', '')))->count(),
                    'missing_description' => $base()->where(fn ($q) => $q->doesntHave('seo')->orWhereHas('seo', fn ($s) => $s->whereNull('meta_description')->orWhere('meta_description', '')))->count(),
                    'missing_h1' => $this->missingH1($class),
                    'missing_image' => $this->missingImage($class),
                    'schema_disabled' => $base()->whereHas('seo', fn ($s) => $s->where('schema_enabled', false))->count(),
                    'noindex' => $base()->whereHas('seo', fn ($s) => $s->where('robots_index', false))->count(),
                    'missing_faqs' => method_exists($class, 'faqs') ? $base()->doesntHave('faqs')->count() : 0,
                ];
            }

            return [
                'rows' => $rows,
                'draft_programmatic' => LandingPage::where('status', ContentStatus::Draft->value)->count(),
                'broken_redirects' => Redirect::brokenCount(),
            ];
        });
    }

    private function missingH1(string $class): int
    {
        return match ($class) {
            Page::class, LandingPage::class => $class::query()->whereNull('h1')->whereNull('title')->count(),
            Service::class => 0, // falls back to name
            default => 0,
        };
    }

    private function missingImage(string $class): int
    {
        return match ($class) {
            Service::class => Service::whereNull('featured_image_id')->whereNull('hero_image_id')->count(),
            default => $class::whereNull('featured_image_id')->count(),
        };
    }
}
