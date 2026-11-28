<?php

namespace App\Services;

use App\Enums\LandingPageType;
use App\Models\Blog;
use App\Models\CaseStudy;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Automatic, capped related-content links per page type. Manual links set in admin come first.
 * Every group is capped (≤ 8) so programmatic pages don't become link farms.
 */
class InternalLinkService
{
    private const CAP = 8;

    /** @return array<string, Collection<int, array{name: string, url: string, description?: string, icon?: string}>> */
    public function for(Model $model): array
    {
        $groups = [];

        if (method_exists($model, 'internalLinks')) {
            $manual = $model->internalLinks()->with('target')->get()
                ->map(fn ($l) => ['name' => $l->anchor, 'url' => $l->href()]);
            if ($manual->isNotEmpty()) {
                $groups['Useful links'] = $manual;
            }
        }

        $groups += match (true) {
            $model instanceof LandingPage => $this->forLanding($model),
            $model instanceof Service => $this->forService($model),
            $model instanceof ServiceCategory => $this->forCategory($model),
            $model instanceof Blog => $this->forBlog($model),
            $model instanceof Portfolio, $model instanceof CaseStudy => $this->forWork($model),
            $model instanceof Page => $this->forPage($model),
            default => [],
        };

        return array_filter($groups, fn ($g) => $g->isNotEmpty());
    }

    public function relatedBlogs(Model $model, int $limit = 3): Collection
    {
        $query = Blog::published()->with(['featuredImage', 'category'])->latest('published_at')->limit($limit);

        $serviceId = match (true) {
            $model instanceof Service => $model->id,
            $model instanceof LandingPage => $model->service_id,
            default => null,
        };

        if ($model instanceof Blog) {
            $query->whereKeyNot($model->id)->where('blog_category_id', $model->blog_category_id);
        } elseif ($serviceId) {
            $linked = (clone $query)->whereHas('services', fn ($q) => $q->whereKey($serviceId))->get();
            if ($linked->count() >= $limit) {
                return $linked;
            }

            return $linked->merge($query->whereNotIn('id', $linked->pluck('id'))->limit($limit - $linked->count())->get());
        }

        return $query->get();
    }

    private function forLanding(LandingPage $page): array
    {
        $base = LandingPage::published()->whereKeyNot($page->id)->limit(self::CAP);
        $groups = [];

        if ($page->service) {
            $svc = $page->service;
            $relatedServices = collect([$this->item($svc)])
                ->merge($svc->children()->published()->limit(self::CAP)->get()->map(fn ($s) => $this->item($s)))
                ->merge(
                    Service::published()
                        ->where('service_category_id', $svc->service_category_id)
                        ->whereKeyNot($svc->id)
                        ->where(fn ($q) => $q->whereNull('parent_id')->orWhere('parent_id', $svc->parent_id ?: $svc->id))
                        ->orderBy('sort_order')
                        ->limit(self::CAP)
                        ->get()
                        ->map(fn ($s) => $this->item($s))
                )
                ->unique('url')
                ->take(self::CAP)
                ->values();

            $groups['Related services'] = $relatedServices;
        }

        if ($page->location_id) {
            $groups[($page->service?->name ?? 'Services').' in other cities'] = (clone $base)
                ->where('service_id', $page->service_id)->whereNull('industry_id')->whereNotNull('location_id')
                ->where('location_id', '!=', $page->location_id)->whereRelation('location', 'type', '!=', 'area')->with('location')->get()
                ->map(fn ($p) => ['name' => $p->title, 'url' => $p->url]);

            $groups['More services in '.$page->location?->display_name] = (clone $base)
                ->where('location_id', $page->location_id)->where('service_id', '!=', $page->service_id)->get()
                ->map(fn ($p) => ['name' => $p->title, 'url' => $p->url]);
        }

        if ($page->industry_id) {
            $groups['Other industries we serve'] = (clone $base)
                ->where('service_id', $page->service_id)->where('page_type', LandingPageType::ServiceIndustry->value)
                ->where('industry_id', '!=', $page->industry_id)->get()
                ->map(fn ($p) => ['name' => $p->title, 'url' => $p->url]);
        } else {
            $groups['Industries we serve'] = (clone $base)
                ->where('service_id', $page->service_id)->where('page_type', LandingPageType::ServiceIndustry->value)->get()
                ->map(fn ($p) => ['name' => $p->title, 'url' => $p->url]);
        }

        return $groups;
    }

    private function forService(Service $service): array
    {
        $groups = [];
        $groups['Related services'] = $service->children()->published()->limit(self::CAP)->get()
            ->merge(Service::published()->where('service_category_id', $service->service_category_id)
                ->whereKeyNot($service->id)->where(fn ($q) => $q->whereNull('parent_id')->orWhere('parent_id', $service->parent_id))
                ->orderBy('sort_order')->limit(self::CAP)->get())
            ->unique('id')->take(self::CAP)->map(fn ($s) => $this->item($s))->values();

        $groups['Locations'] = $service->landingPages()->published()->serviceLocation()->whereRelation('location', 'type', '!=', 'area')->limit(18)->get()
            ->map(fn ($p) => ['name' => $p->title, 'url' => $p->url]);

        $groups['Industries'] = $service->landingPages()->published()->serviceIndustry()->limit(self::CAP)->get()
            ->map(fn ($p) => ['name' => $p->title, 'url' => $p->url]);

        return $groups;
    }

    private function forCategory(ServiceCategory $category): array
    {
        $others = Service::published()
            ->where('service_category_id', '!=', $category->id)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(self::CAP)
            ->get()
            ->map(fn ($s) => $this->item($s))
            ->values();

        return $others->isNotEmpty() ? ['Related services' => $others] : [];
    }

    private function forBlog(Blog $blog): array
    {
        $linked = $blog->services()->published()->limit(4)->get();
        if ($linked->count() < 4) {
            $categoryIds = $linked->pluck('service_category_id')->filter()->unique()->values();
            $fallback = Service::published()
                ->when($linked->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $linked->pluck('id')))
                ->when($categoryIds->isNotEmpty(), fn ($q) => $q->orderByRaw('service_category_id IN ('.implode(',', $categoryIds->map('intval')->all()).') desc'))
                ->orderByRaw('parent_id is null desc')
                ->orderBy('sort_order')
                ->limit(4 - $linked->count())
                ->get();
            $linked = $linked->merge($fallback)->unique('id')->take(4);
        }

        return ['Related services' => $linked->map(fn ($s) => $this->item($s))->values()];
    }

    private function forWork(Portfolio|CaseStudy $work): array
    {
        $service = $work->service;
        $query = Service::published()->orderBy('sort_order');
        if ($service) {
            $items = collect([$this->item($service)])
                ->merge(
                    (clone $query)
                        ->where('service_category_id', $service->service_category_id)
                        ->whereKeyNot($service->id)
                        ->whereNull('parent_id')
                        ->limit(3)
                        ->get()
                        ->map(fn ($s) => $this->item($s))
                )
                ->unique('url')
                ->take(4)
                ->values();

            return ['Related services' => $items];
        }

        return [
            'Related services' => $query->whereNull('parent_id')->limit(4)->get()->map(fn ($s) => $this->item($s))->values(),
        ];
    }

    private function forPage(Page $page): array
    {
        return ['In this section' => $page->children()->published()->limit(self::CAP)->get()
            ->map(fn ($p) => ['name' => $p->title, 'url' => $p->url])];
    }

    private function item(Service $s): array
    {
        return ['name' => $s->menu_label ?: $s->name, 'url' => $s->url, 'description' => $s->short_description, 'icon' => $s->icon];
    }
}
