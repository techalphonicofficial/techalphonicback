<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Service;
use App\Models\Testimonial;
use App\Support\SitePresenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Serialises page-builder blocks for the Next.js frontend. Everything the Blade block partials
 * looked up at render time (media, services, testimonials, FAQs…) is resolved here into `extra`.
 */
class BlockPresenter
{
    public function __construct(
        private SitePresenter $present,
        private TemplateRenderer $renderer,
        private InternalLinkService $links,
        private FaqService $faqs,
    ) {}

    /** @param  Collection<int, \App\Models\PageBlock>  $blocks */
    public function present(Collection $blocks, ?Model $context = null, array $vars = []): array
    {
        $blocks = $blocks->filter(fn ($b) => $b->is_visible)->values();
        $ids = [];
        $rendered = $blocks->map(function ($b) use ($vars, &$ids) {
            $d = $vars ? $this->renderer->render($b->data ?? [], $vars) : ($b->data ?? []);
            $ids = array_merge($ids, SitePresenter::mediaIds($d));

            return [$b->type, $d];
        });
        $media = $this->present->mediaMap($ids);

        return $rendered->map(fn ($pair) => $this->block($pair[0], $pair[1], $context, $media))->all();
    }

    /** A single block built in code (e.g. the default service layout reuses block partials). */
    public function one(string $type, array $data, ?Model $context = null, bool $dark = false): array
    {
        $block = $this->block($type, $data, $context, $this->present->mediaMap(SitePresenter::mediaIds($data)));
        $block['dark'] = $dark;

        return $block;
    }

    private function block(string $type, array $d, ?Model $context, array $media): array
    {
        $bg = $d['background'] ?? 'white';
        $ownIds = SitePresenter::mediaIds($d);

        return [
            'type' => $type,
            'data' => $d,
            'background' => $bg,
            'dark' => $bg === 'dark',
            'center' => ($d['align'] ?? 'left') === 'center',
            'media' => (object) array_intersect_key($media, array_flip($ownIds)),
            'extra' => $this->extra($type, $d, $context),
        ];
    }

    private function extra(string $type, array $d, ?Model $context): array|object
    {
        $serviceId = $context instanceof Service ? $context->id : ($context instanceof LandingPage ? $context->service_id : null);

        return match ($type) {
            'service_cards' => ['services' => $this->serviceCards($d)],
            'related_services' => ['services' => $this->relatedServices($context)],
            'testimonials' => ['testimonials' => $this->testimonials($d)],
            'case_studies' => ['studies' => CaseStudy::published()->with(['featuredImage', 'industry'])
                ->when($context instanceof Service, fn ($q) => $q->orderByRaw('service_id = ? desc', [$context->id]))
                ->orderBy('sort_order')->limit((int) ($d['limit'] ?? 3))->get()
                ->map(fn ($c) => $this->present->caseStudyCard($c))->all()],
            'industries' => $this->industries($d, $serviceId),
            'related_blogs' => ['posts' => ($context
                    ? $this->links->relatedBlogs($context, (int) ($d['limit'] ?? 3))
                    : Blog::published()->with(['featuredImage', 'category'])->latest('published_at')->limit((int) ($d['limit'] ?? 3))->get())
                ->map(fn ($b) => $this->present->blogCard($b))->values()->all()],
            'related_locations' => ['pages' => LandingPage::published()->serviceLocation()
                ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
                ->when($context instanceof LandingPage, fn ($q) => $q->whereKeyNot($context->id))
                ->with('location')->limit((int) ($d['limit'] ?? 12))->get()
                ->map(fn ($p) => ['name' => $p->location?->name ?? $p->title, 'url' => $p->url])->all()],
            'faq' => ['faqs' => $context ? $this->present->faqs($this->faqs->for($context)) : []],
            'contact_form' => [
                'service' => $context instanceof Service ? $context->name : ($context instanceof LandingPage ? $context->service?->name : null),
                'location' => $context instanceof LandingPage ? $context->location?->name : null,
            ],
            'comparison_table' => $this->table((string) ($d['table'] ?? '')),
            default => (object) [],
        };
    }

    private function serviceCards(array $d): array
    {
        $ids = array_values(array_filter((array) ($d['service_ids'] ?? [])));
        $services = $ids
            ? Service::published()->whereIn('id', $ids)->get()->sortBy(fn ($s) => array_search($s->id, $ids))
            : Service::published()->where('is_featured', true)->orderBy('sort_order')->limit((int) ($d['limit'] ?? 8))->get();

        return $services->map(fn ($s) => $this->present->serviceCard($s))->values()->all();
    }

    private function relatedServices(?Model $context): array
    {
        $services = collect();
        if ($context instanceof Service) {
            $services = Service::published()->where('service_category_id', $context->service_category_id)->whereKeyNot($context->id)->orderBy('sort_order')->limit(4)->get();
        } elseif ($context instanceof LandingPage && $context->service) {
            $services = Service::published()->where('service_category_id', $context->service->service_category_id)->whereKeyNot($context->service_id)->orderBy('sort_order')->limit(4)->get();
        }
        if ($services->isEmpty()) {
            $services = Service::published()->where('is_featured', true)->limit(4)->get();
        }

        return $services->map(fn ($s) => $this->present->serviceCard($s))->values()->all();
    }

    private function testimonials(array $d): array
    {
        $ids = array_filter((array) ($d['testimonial_ids'] ?? []));
        $items = $ids ? Testimonial::active()->with('photo')->whereIn('id', $ids)->get()
            : Testimonial::active()->with('photo')->limit((int) ($d['limit'] ?? 3))->get();

        return $items->map(fn ($t) => $this->present->testimonial($t))->all();
    }

    private function industries(array $d, ?int $serviceId): array
    {
        $industries = Industry::active()->limit((int) ($d['limit'] ?? 10))->get();
        $pages = LandingPage::published()->serviceIndustry()
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
            ->whereIn('industry_id', $industries->pluck('id'))->get()->keyBy('industry_id');

        return ['industries' => $industries->map(fn ($i) => $this->present->industry($i) + ['url' => $pages->get($i->id)?->url])->all()];
    }

    /** "Head | Head\nCell | Cell" → head + rows (same parsing as the Blade partial). */
    private function table(string $raw): array
    {
        $lines = array_values(array_filter(preg_split('/\r?\n/', $raw)));
        $rows = array_map(fn ($l) => array_map('trim', explode('|', $l)), $lines);
        $head = array_shift($rows) ?? [];

        return ['head' => $head, 'rows' => $rows];
    }
}
