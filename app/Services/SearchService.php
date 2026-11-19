<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Site search over published content. Uses indexed prefix/LIKE matching on titles first; small enough for
 * thousands of rows. Swap for MySQL FULLTEXT or Scout if content grows into the hundreds of thousands.
 */
class SearchService
{
    /** @return Collection<int, array{type: string, title: string, url: string, excerpt: ?string}> */
    public function search(string $term, int $limit = 30): Collection
    {
        $term = trim(Str::limit($term, 80, ''));
        if (mb_strlen($term) < 2) {
            return collect();
        }
        $like = '%'.addcslashes($term, '%_\\').'%';

        $services = Service::published()->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('short_description', 'like', $like))
            ->limit(10)->get()->map(fn ($s) => ['type' => 'Service', 'title' => $s->name, 'url' => $s->url, 'excerpt' => $s->short_description]);

        $landing = LandingPage::published()
            ->where(fn ($q) => $q->doesntHave('seo')->orWhereHas('seo', fn ($s) => $s->where('robots_index', true)))
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('h1', 'like', $like))
            ->limit(10)->get()->map(fn ($p) => ['type' => 'Service', 'title' => $p->title, 'url' => $p->url, 'excerpt' => null]);

        $pages = Page::published()->where('show_in_search', true)->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like))
            ->limit(8)->get()->map(fn ($p) => ['type' => 'Page', 'title' => $p->title, 'url' => $p->url, 'excerpt' => $p->excerpt]);

        $blogs = Blog::published()->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like))
            ->latest('published_at')->limit(10)->get()->map(fn ($b) => ['type' => 'Blog', 'title' => $b->title, 'url' => $b->url, 'excerpt' => $b->excerpt]);

        $industries = Industry::active()->where('name', 'like', $like)->limit(5)->get()
            ->map(fn ($i) => ($p = $i->primaryLandingPage()) ? ['type' => 'Industry', 'title' => $p->title, 'url' => $p->url, 'excerpt' => $i->description] : null)
            ->filter();

        return $services->concat($pages)->concat($landing)->concat($industries)->concat($blogs)
            ->unique('url')->take($limit)->values();
    }
}
