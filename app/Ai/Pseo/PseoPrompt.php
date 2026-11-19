<?php

namespace App\Ai\Pseo;

use App\Ai\AiSettings;
use App\Models\LandingPage;
use App\Services\SettingsService;
use Illuminate\Support\Str;

/**
 * Prompt for one programmatic SEO page. The system prompt is identical for every page (so providers can cache it);
 * everything page-specific goes in the user message as grounding facts.
 *
 * Bump VERSION whenever the wording changes; it is stored on each generation for comparison.
 */
class PseoPrompt
{
    public const VERSION = 'pseo-v1';

    public function __construct(private SettingsService $site, private AiSettings $ai) {}

    public function system(): string
    {
        $business = $this->site->get('site_name');
        $voice = trim((string) $this->ai->get('ai_brand_voice'));
        $extra = trim((string) $this->ai->get('ai_instructions'));

        return trim(<<<TXT
        You write landing pages for {$business}, a company that sells the services described in each request. Each page targets one
        combination of a service with a location, an industry, or both. These pages are published on the company's own website, so
        they must read as genuinely useful to a business owner deciding whether to hire {$business}, not as search-engine filler.

        What makes a page good:
        - It is specific to its combination. A page about a service in one city should only be possible to write for that city;
          a page for an industry should reflect how that industry buys, its typical challenges and what success looks like for it.
          Swapping the place or industry name into a sibling page must not produce this page.
        - It sounds like an experienced practitioner explaining their approach: concrete activities, trade-offs and what the
          client can expect, rather than adjectives.
        - The focus keyword appears in the H1, the introduction and naturally a few more times. Never stuff keywords or repeat
          the place name in every sentence.

        Accuracy rules, which matter more than persuasiveness:
        - Use only the facts given in the request about {$business}, its service, the location and the industry. Do not invent
          statistics, percentages, client names, case studies, awards, years in business, team sizes, prices, guarantees,
          office addresses or testimonials. When a claim would need a number you were not given, make it qualitatively.
        - Local and industry context may draw on widely known, stable facts (a city's major business districts, an industry's
          regulatory environment), stated without precise figures. If you are unsure whether something is true, leave it out.
        - Do not claim a physical office in a location unless the request says there is one.

        Format rules:
        - Return only the JSON object described by the schema.
        - HTML fields may use <p>, <h2>, <h3>, <ul>, <ol>, <li>, <strong> and <em>. No <h1>, links, images, scripts, inline styles
          or class attributes. Plain-text fields contain no HTML or Markdown.
        - Do not use {{double braces}}; write the actual names.
        - Respect the word and character ranges in the schema descriptions.
        TXT
            .($voice !== '' ? "\n\nBrand voice: {$voice}" : '')
            .($extra !== '' ? "\n\nAdditional instructions from the site owner:\n{$extra}" : ''));
    }

    /** @param  string[]  $fields */
    public function user(LandingPage $page, array $fields): string
    {
        $page->loadMissing(['service', 'location', 'industry', 'seo']);
        $service = $page->service;
        $location = $page->location;
        $industry = $page->industry;
        $vars = $page->templateVariables();

        $facts = array_filter([
            'business' => array_filter([
                'name' => $this->site->get('site_name'),
                'tagline' => $this->site->get('site_tagline'),
                'headquarters' => collect([$this->site->get('address_locality'), $this->site->get('address_region'), $this->site->get('country')])->filter()->implode(', ') ?: null,
                'founded' => $this->site->get('founding_year'),
            ]),
            'service' => array_filter([
                'name' => $service?->name,
                'summary' => $service?->short_description,
                'description' => $this->plain($service?->description ?: $service?->content, 1500),
                'benefits' => collect($service?->benefits ?? [])->map(fn ($b) => trim(($b['title'] ?? '').': '.($b['text'] ?? ''), ': '))->filter()->values()->all(),
                'process' => collect($service?->process ?? [])->pluck('title')->filter()->values()->all(),
                'technologies' => $service?->technologies ?: null,
            ]),
            'location' => $location ? array_filter([
                'name' => $location->name,
                'type' => $location->type,
                'state' => $location->state,
                'country' => $location->country,
                'about' => $this->plain($location->description, 800),
                'local_facts' => $location->local_facts ?: null,
            ]) : null,
            'industry' => $industry ? array_filter([
                'name' => $industry->name,
                'plural' => $industry->plural_name,
                'about' => $this->plain($industry->description, 800),
                'challenges' => collect($industry->challenges ?? [])->pluck('title')->filter()->values()->all(),
                'solutions' => collect($industry->solutions ?? [])->pluck('title')->filter()->values()->all(),
            ]) : null,
        ]);

        $render = fn (?string $s) => $s ? strtr($s, collect($vars)->mapWithKeys(fn ($v, $k) => ['{{'.$k.'}}' => $v])->all()) : null;
        $target = array_filter([
            'page_type' => $page->page_type?->label(),
            'working_title' => $page->title,
            'suggested_focus_keyword' => $render($page->seo?->focus_keyword),
        ]);

        $siblings = $this->siblingHeadings($page);
        $requested = collect($fields)->map(fn ($f) => AiSettings::FIELDS[$f] ?? $f)->implode(', ');

        $out = "Write the landing page described below.\n\n"
            ."<page>\n".json_encode($target, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n</page>\n\n"
            ."<facts>\n".json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n</facts>\n\n";
        if ($siblings) {
            $out .= "<sibling_pages>\nThese pages already exist for the same service. Use different angles, headings and examples so this page doesn't duplicate them:\n"
                .implode("\n", $siblings)."\n</sibling_pages>\n\n";
        }

        return $out."Parts to write: {$requested}.";
    }

    /** Titles + section headings of up to 12 other pages for the same service. */
    private function siblingHeadings(LandingPage $page): array
    {
        return LandingPage::query()
            ->where('service_id', $page->service_id)
            ->whereKeyNot($page->getKey())
            ->latest('updated_at')
            ->limit(12)
            ->get(['id', 'title', 'sections'])
            ->map(function ($p) {
                $headings = collect($p->sections ?? [])->pluck('heading')->filter()->take(4)->implode('; ');

                return '- '.$p->title.($headings ? " (sections: {$headings})" : '');
            })
            ->all();
    }

    private function plain(?string $html, int $limit): ?string
    {
        if (! $html) {
            return null;
        }
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $text === '' ? null : Str::limit($text, $limit);
    }
}
