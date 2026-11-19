<?php

namespace App\Services;

use App\Models\LandingPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Internal on-page checklist. A content-quality helper for editors — it does not model Google's ranking.
 */
class SeoAuditService
{
    public function __construct(private TemplateRenderer $renderer, private FaqService $faqs) {}

    /** @return array{score: int, checks: array<int, array{label: string, pass: bool, weight: int, hint: string}>} */
    public function audit(Model $model): array
    {
        $seo = $model->seo;
        $vars = $model instanceof LandingPage ? $model->templateVariables() : [];
        $r = fn ($v) => trim(strip_tags((string) $this->renderer->render($v, $vars)));

        $title = $r($seo?->meta_title);
        $desc = $r($seo?->meta_description);
        $h1 = $r($model->h1 ?? $model->hero_heading ?? $model->title ?? $model->name);
        $html = $this->bodyHtml($model);
        $text = $r($html);
        $words = str_word_count($text);
        $focus = mb_strtolower($r($seo?->focus_keyword));

        preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\']/i', $html, $links);
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $internal = collect($links[1])->filter(fn ($u) => str_starts_with($u, '/') || parse_url($u, PHP_URL_HOST) === $host)->count();
        $external = count($links[1]) - $internal;
        if (method_exists($model, 'internalLinks')) {
            $internal += $model->internalLinks()->count();
        }

        preg_match_all('/<img\b[^>]*>/i', $html, $imgs);
        $imgsWithoutAlt = collect($imgs[0])->filter(fn ($tag) => ! preg_match('/\balt=["\'][^"\']+["\']/i', $tag))->count();
        $featured = method_exists($model, 'featuredImage') ? $model->featuredImage : null;

        $checks = [
            ['H1 present', $h1 !== '', 10, 'Add a clear H1 that describes the page.'],
            ['Meta title set', $title !== '', 10, 'Write a unique meta title.'],
            ['Meta title length 30–60', mb_strlen($title) >= 30 && mb_strlen($title) <= 60, 5, 'Currently '.mb_strlen($title).' characters.'],
            ['Meta description set', $desc !== '', 10, 'Write a compelling meta description.'],
            ['Meta description length 120–160', mb_strlen($desc) >= 120 && mb_strlen($desc) <= 160, 5, 'Currently '.mb_strlen($desc).' characters.'],
            ['Focus keyword set', $focus !== '', 5, 'Choose one primary keyword.'],
            ['Focus keyword in title', $focus && Str::contains(mb_strtolower($title), $focus), 5, 'Use the focus keyword in the meta title.'],
            ['Focus keyword in H1', $focus && Str::contains(mb_strtolower($h1), $focus), 5, 'Use the focus keyword (or a close variant) in the H1.'],
            ['Canonical resolvable', true, 5, 'Canonical defaults to the page URL when blank.'],
            ['Featured image with alt text', $featured && $featured->alt, 5, 'Add a featured image and fill its alt text.'],
            ['All content images have alt', $imgsWithoutAlt === 0, 5, $imgsWithoutAlt.' image(s) missing alt.'],
            ['Content length ≥ 300 words', $words >= 300, 10, "Currently $words words — thin pages rarely rank."],
            ['Internal links ≥ 2', $internal >= 2, 5, "Found $internal internal link(s)."],
            ['External reference link', $external >= 1, 2, 'Optional: cite an authoritative source.'],
            ['FAQs on page', $this->faqs->for($model)->isNotEmpty(), 5, 'Add 3–6 FAQs that answer real questions.'],
            ['Schema enabled', ! $seo || $seo->schema_enabled, 3, 'Structured data is disabled for this page.'],
            ['Open Graph title/image', ($seo?->og_title || $title) && ($seo?->og_image_id || $featured), 3, 'Set an OG image for social sharing.'],
            ['Indexable', ! $seo || $seo->robots_index, 2, 'Page is set to noindex.'],
        ];

        $total = array_sum(array_column($checks, 2));
        $earned = collect($checks)->filter(fn ($c) => $c[1])->sum(fn ($c) => $c[2]);

        return [
            'score' => (int) round($earned / $total * 100),
            'words' => $words,
            'checks' => array_map(fn ($c) => ['label' => $c[0], 'pass' => (bool) $c[1], 'weight' => $c[2], 'hint' => $c[3]], $checks),
        ];
    }

    private function bodyHtml(Model $model): string
    {
        $parts = [];
        foreach (['introduction', 'description', 'content', 'hero_description', 'excerpt', 'challenge', 'solution', 'results'] as $f) {
            $parts[] = (string) $model->getAttribute($f);
        }
        if (method_exists($model, 'blocks')) {
            foreach ($model->blocks as $block) {
                $data = (array) $block->data; // array_walk_recursive needs a real variable, not a cast attribute
                array_walk_recursive($data, function ($v) use (&$parts) {
                    if (is_string($v)) {
                        $parts[] = $v;
                    }
                });
            }
        }
        foreach (['benefits', 'process', 'why_choose_us', 'features'] as $f) {
            $v = $model->getAttribute($f);
            if (is_array($v)) {
                array_walk_recursive($v, function ($x) use (&$parts) {
                    if (is_string($x)) {
                        $parts[] = $x;
                    }
                });
            }
        }

        return implode("\n", $parts);
    }
}
