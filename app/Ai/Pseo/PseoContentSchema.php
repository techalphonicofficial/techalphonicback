<?php

namespace App\Ai\Pseo;

use App\Support\Icons;

/**
 * JSON Schema for programmatic page content, built from the field groups enabled in AI settings.
 * Kept to the subset all three providers accept in strict mode: every property required, no extra properties,
 * no numeric/length constraints (counts and lengths are given in descriptions and enforced after parsing).
 */
class PseoContentSchema
{
    /** Icons the model may pick for benefit / why-choose-us cards (must exist in App\Support\Icons). */
    public const ICONS = ['chart', 'trending-up', 'target', 'users', 'map-pin', 'shield', 'zap', 'rocket', 'layers', 'search',
        'globe', 'clock', 'check-circle', 'award', 'sparkles', 'message', 'settings', 'cpu', 'cloud', 'code', 'smartphone',
        'palette', 'megaphone', 'banknote', 'briefcase', 'building', 'heart', 'star', 'file-text', 'link', 'wrench', 'store', 'cart'];

    /** @param  string[]  $fields */
    public static function for(array $fields): array
    {
        $str = fn (string $d) => ['type' => 'string', 'description' => $d];
        $list = fn (string $d, array $props) => [
            'type' => 'array',
            'description' => $d,
            'items' => ['type' => 'object', 'properties' => $props, 'required' => array_keys($props), 'additionalProperties' => false],
        ];
        $icon = ['type' => 'string', 'enum' => self::icons(), 'description' => 'Icon that best fits the card.'];

        $groups = [
            'hero' => [
                'h1' => $str('Page H1, 40–70 characters, containing the focus keyword naturally.'),
                'hero_subheading' => $str('Short supporting line under the H1, 4–10 words, no full stop.'),
                'introduction' => $str('HTML: one or two <p> paragraphs, 60–110 words in total, specific to this exact page.'),
            ],
            'content' => [
                'content' => $str('HTML main body, 600–900 words. Use <h2>/<h3>, <p>, <ul>/<ol>, <strong>. No <h1>, no links, no images.'),
            ],
            'sections' => [
                'sections' => $list('3–4 extra sections that only make sense for this page (local market, industry challenges, etc.).', [
                    'heading' => $str('H2 heading, plain text.'),
                    'content' => $str('HTML, 90–160 words: <p>, <ul>, <strong> only.'),
                ]),
            ],
            'benefits' => [
                'benefits' => $list('4–6 benefits of this service for this audience.', [
                    'title' => $str('2–6 words.'),
                    'text' => $str('One sentence, 12–25 words, plain text.'),
                    'icon' => $icon,
                ]),
            ],
            'process' => [
                'process' => $list('4–6 steps of how the work is delivered.', [
                    'title' => $str('Step name, 2–5 words.'),
                    'text' => $str('What happens in this step, 20–40 words, plain text.'),
                ]),
            ],
            'why_choose_us' => [
                'why_choose_us' => $list('3–4 reasons to choose the business, grounded only in the facts provided.', [
                    'title' => $str('2–6 words.'),
                    'text' => $str('One or two sentences, plain text.'),
                    'icon' => $icon,
                ]),
            ],
            'faqs' => [
                'faqs' => $list('5–7 questions real buyers for this page would ask.', [
                    'question' => $str('The question, ending with "?".'),
                    'answer' => $str('HTML answer, 40–90 words, in <p> tags.'),
                ]),
            ],
            'seo' => [
                'meta_title' => $str('50–60 characters including the business name at the end.'),
                'meta_description' => $str('140–160 characters with a clear benefit and call to action.'),
                'focus_keyword' => $str('The main search phrase this page should rank for, lowercase.'),
            ],
        ];

        $properties = [];
        foreach ($fields as $f) {
            $properties += $groups[$f] ?? [];
        }

        return ['type' => 'object', 'properties' => $properties, 'required' => array_keys($properties), 'additionalProperties' => false];
    }

    public static function icons(): array
    {
        return array_values(array_intersect(self::ICONS, Icons::names()));
    }
}
