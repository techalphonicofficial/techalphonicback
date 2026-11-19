<?php

namespace App\Services;

use App\Enums\LandingPageType;
use App\Models\Blog;
use App\Models\LandingPage;
use App\Models\Page;
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
            $model instanceof ServiceCategory => [],
            $model instanceof Blog => $this->forBlog($model),
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
            $groups[$page->service->name.' services'] = collect([['name' => $page->service->name, 'url' => $page->service->url]])
                ->merge($page->service->children()->published()->limit(4)->get()->map(fn ($s) => $this->item($s)));
        }

        if ($page->location_id) {
            $groups[($page->service?->name ?? 'Services').' in other cities'] = (clone $base)
                ->where('service_id', $page->service_id)->whereNull('industry_id')->whereNotNull('location_id')
                ->where('location_id', '!=', $page->location_id)->with('location')->get()
                ->map(fn ($p) => ['name' => $p->title, 'url' => $p->url]);

            $groups['More services in '.$page->location?->name] = (clone $base)
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

        $groups['Locations'] = $service->landingPages()->published()->serviceLocation()->limit(18)->get()
            ->map(fn ($p) => ['name' => $p->title, 'url' => $p->url]);

        $groups['Industries'] = $service->landingPages()->published()->serviceIndustry()->limit(self::CAP)->get()
            ->map(fn ($p) => ['name' => $p->title, 'url' => $p->url]);

        return $groups;
    }

    private function forBlog(Blog $blog): array
    {
        return ['Related services' => $blog->services()->published()->limit(4)->get()->map(fn ($s) => $this->item($s))];
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
