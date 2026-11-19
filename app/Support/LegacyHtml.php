<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Converts HTML from the old WordPress site into clean, design-neutral HTML for the new CMS.
 *  - post():      Gutenberg post body → semantic HTML (drops the Easy-TOC widget, classes, inline styles).
 *  - elementor(): Elementor page → ['intro', 'content', 'faqs', 'images'], keeping only real copy
 *                 (headings, text, icon boxes, lists, FAQs) and dropping forms, buttons, counters and layout.
 */
class LegacyHtml
{
    private const ALLOWED = ['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'a', 'br', 'blockquote',
        'table', 'thead', 'tbody', 'tr', 'th', 'td'];

    public function __construct(private string $legacyHost = 'techalphonic.com') {}

    public function post(string $html): string
    {
        [$dom, $root] = $this->load($html);
        $xp = new DOMXPath($dom);
        foreach ($xp->query('//*[@id="ez-toc-container"] | //script | //style | //noscript | //figure | //img | //iframe') as $n) {
            $n->parentNode?->removeChild($n);
        }

        return $this->tidy($this->sanitizeChildren($root));
    }

    /** @return array{title: ?string, intro: ?string, content: string, faqs: array<int, array{q: string, a: string}>, process: array<int, array{title: string, text: string}>, images: string[]} */
    public function elementor(string $html): array
    {
        [$dom] = $this->load($html);
        $xp = new DOMXPath($dom);
        foreach ($xp->query('//*[@id="ez-toc-container"] | //script | //style | //noscript') as $n) {
            $n->parentNode?->removeChild($n);
        }

        $title = null;
        $intro = null;
        $blocks = [];
        $faqs = [];
        $images = [];
        $seen = [];
        $skipNextText = false;

        $widgets = $xp->query('//*[contains(concat(" ", normalize-space(@class), " "), " elementor-widget ")]');
        foreach ($widgets as $w) {
            /** @var DOMElement $w */
            if ($this->insideOtherWidget($w)) {
                continue;
            }
            $type = strtok((string) $w->getAttribute('data-widget_type'), '.');
            $text = $this->text($w);

            if (str_contains($type, 'accordion') || str_contains($type, 'toggle') || str_contains($type, 'faq')) {
                $faqs = array_merge($faqs, $this->faqPairs($xp, $w));

                continue;
            }
            if ($type === 'image') {
                foreach ($xp->query('.//img', $w) as $img) {
                    $src = $img->getAttribute('data-src') ?: $img->getAttribute('src');
                    if (str_contains($src, '/wp-content/uploads/')) {
                        $images[] = $src;
                    }
                }

                continue;
            }
            if ($text === '') {
                continue;
            }

            $html = match ($type) {
                'elementskit-heading', 'heading' => $this->headingBlock($w, $type, $text, $title),
                'text-editor' => $this->sanitizeChildren($this->firstContainer($w)),
                'icon-box', 'elementskit-icon-box' => $this->iconBox($xp, $w),
                'icon-list' => $this->iconList($xp, $w),
                default => null, // buttons, forms, counters, carousels, social icons, raw html …
            };
            if (! $html) {
                continue;
            }
            // Headings that only introduce template widgets (testimonial sliders, FAQ accordions, forms) are dropped with their blurb.
            if ($type !== 'text-editor' && preg_match('/^<h2>(what our clients? says?|question|frequently asked|faq|testimonial|our clients|our valued clients|join the team)/i', $html)) {
                $skipNextText = true;

                continue;
            }
            if ($type === 'text-editor' && $skipNextText) {
                $skipNextText = false;

                continue;
            }
            $skipNextText = false;
            $html = $this->tidy($html);
            $key = md5(strip_tags($html));
            if ($html === '' || isset($seen[$key])) {
                continue; // Elementor often repeats blocks for mobile/desktop
            }
            $seen[$key] = true;

            // The first paragraph right after the H1 becomes the hero intro.
            if ($intro === null && $title !== null && $type === 'text-editor' && ! $blocks) {
                $intro = trim(preg_replace('/\s+/', ' ', strip_tags($html)));

                continue;
            }
            $blocks[] = $html;
        }

        // Numbered accordions ("01/ Strategic Consultation") are process steps, not questions.
        $process = [];
        $faqs = array_values(array_filter($faqs, function ($f) use (&$process) {
            if (preg_match('#^\s*0?\d+\s*[/.)-]\s*(.+)$#u', $f['q'], $m)) {
                $process[] = ['title' => trim($m[1]), 'text' => trim(html_entity_decode(strip_tags($f['a']), ENT_QUOTES | ENT_HTML5, 'UTF-8'))];

                return false;
            }

            return true;
        }));

        return [
            'title' => $title,
            'intro' => $intro,
            'content' => implode("\n", $blocks),
            'faqs' => $this->uniqueFaqs($faqs),
            'process' => $process,
            'images' => array_values(array_unique($images)),
        ];
    }

    /** Rewrites absolute links to the old domain as root-relative paths (redirects take care of renamed URLs). */
    public function relativeLinks(string $html): string
    {
        $host = preg_quote($this->legacyHost, '#');

        return preg_replace_callback('#href="https?://(?:www\.)?'.$host.'(/[^"]*)?"#i', function ($m) {
            $path = $m[1] ?? '/';
            if ($path === '' || $path === '/') {
                return 'href="/"';
            }
            $parts = parse_url($path);
            $p = '/'.trim($parts['path'] ?? '', '/');
            $p = $p === '/' ? '/' : $p.'/';

            return 'href="'.$p.(isset($parts['fragment']) ? '#'.$parts['fragment'] : '').'"';
        }, $html);
    }

    // ---------------------------------------------------------------------------------------------

    private function load(string $html): array
    {
        $dom = new DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="__root">'.$html.'</div>', LIBXML_NONET | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return [$dom, $dom->getElementById('__root')];
    }

    private function insideOtherWidget(DOMElement $w): bool
    {
        for ($p = $w->parentNode; $p instanceof DOMElement; $p = $p->parentNode) {
            if (str_contains(' '.$p->getAttribute('class').' ', ' elementor-widget ')) {
                return true;
            }
        }

        return false;
    }

    private function firstContainer(DOMElement $w): DOMNode
    {
        foreach ($w->childNodes as $c) {
            if ($c instanceof DOMElement && str_contains($c->getAttribute('class'), 'elementor-widget-container')) {
                return $c;
            }
        }

        return $w;
    }

    private function headingBlock(DOMElement $w, string $type, string $text, ?string &$title): ?string
    {
        $isH1 = (bool) (new DOMXPath($w->ownerDocument))->query('.//h1', $w)->length;
        if ($title === null && $isH1 && mb_strlen($text) > 8) {
            $title = $text;

            return null;
        }
        // Short Elementor headings are eyebrow labels ("Our Process") — skip; real headings become H2s.
        if ($type === 'heading' && mb_strlen($text) < 25) {
            return null;
        }
        if (preg_match('/^(fill in the details|get a free quote|contact us now|lets now|looking for)/i', $text)) {
            return null;
        }

        return '<h2>'.e($text).'</h2>';
    }

    private function iconBox(DOMXPath $xp, DOMElement $w): ?string
    {
        $t = $xp->query('.//*[contains(@class,"icon-box-title") or contains(@class,"elementskit-info-box-title")]', $w)->item(0);
        $d = $xp->query('.//*[contains(@class,"icon-box-description") or contains(@class,"box-body")]//p | .//*[contains(@class,"icon-box-description")]', $w)->item(0);
        $title = $t ? $this->text($t) : '';
        $desc = $d ? $this->text($d) : '';
        if ($title === '' && $desc === '') {
            return null;
        }
        if ($desc === '' && mb_strlen($title) < 30) {
            return null; // badge-like boxes ("7+ Years of Trust")
        }

        return ($title !== '' ? '<h3>'.e($title).'</h3>' : '').($desc !== '' ? '<p>'.e($desc).'</p>' : '');
    }

    private function iconList(DOMXPath $xp, DOMElement $w): ?string
    {
        $items = [];
        foreach ($xp->query('.//li', $w) as $li) {
            $t = $this->text($li);
            if ($t !== '' && ! in_array(strtolower($t), ['home', '/'], true)) {
                $items[] = '<li>'.e($t).'</li>';
            }
        }

        return count($items) >= 2 ? '<ul>'.implode('', $items).'</ul>' : null;
    }

    private function faqPairs(DOMXPath $xp, DOMElement $w): array
    {
        $pairs = [];
        $titles = $xp->query('.//*[contains(@class,"tab-title") or contains(@class,"elementskit-btn-link") or contains(@class,"accordion-title") or self::summary]', $w);
        $bodies = $xp->query('.//*[contains(@class,"tab-content") or contains(@class,"elementskit-card-body") or contains(@class,"accordion-content")]', $w);
        foreach ($titles as $i => $t) {
            $q = $this->text($t);
            $body = $bodies->item($i);
            $a = $body ? $this->tidy($this->sanitizeChildren($body)) : '';
            if ($q !== '' && strip_tags($a) !== '') {
                $pairs[] = ['q' => $q, 'a' => str_starts_with($a, '<') ? $a : '<p>'.$a.'</p>'];
            }
        }

        return $pairs;
    }

    private function uniqueFaqs(array $faqs): array
    {
        $out = [];
        foreach ($faqs as $f) {
            $out[mb_strtolower($f['q'])] ??= $f;
        }

        return array_values($out);
    }

    private function text(DOMNode $n): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($n->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /** Serialises children keeping only whitelisted tags (no attributes except a safe href). */
    private function sanitizeChildren(DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= $this->sanitizeNode($child);
        }

        return $out;
    }

    private function sanitizeNode(DOMNode $n): string
    {
        if ($n->nodeType === XML_TEXT_NODE) {
            return e($n->textContent, false);
        }
        if (! $n instanceof DOMElement) {
            return '';
        }
        $tag = strtolower($n->tagName);
        if (in_array($tag, ['script', 'style', 'noscript', 'form', 'button', 'svg', 'input', 'select', 'textarea'], true)) {
            return '';
        }
        $inner = $this->sanitizeChildren($n);
        if ($tag === 'h1') {
            $tag = 'h2';
        }
        if ($tag === 'b') {
            $tag = 'strong';
        }
        if ($tag === 'i') {
            $tag = 'em';
        }
        if (! in_array($tag, self::ALLOWED, true)) {
            // unwrap divs/spans; keep block separation for divs
            return in_array($tag, ['div', 'section'], true) && trim(strip_tags($inner)) !== '' && ! preg_match('#^\s*<(p|h[2-4]|ul|ol|table|blockquote)#', $inner)
                ? '<p>'.$inner.'</p>' : $inner;
        }
        if ($tag === 'br') {
            return '<br>';
        }
        if ($tag === 'a') {
            $href = trim($n->getAttribute('href'));
            if ($href === '' || preg_match('#^(javascript|data):#i', $href) || $href === '#') {
                return $inner;
            }
            $external = preg_match('#^https?://#i', $href) && ! preg_match('#^https?://(www\.)?'.preg_quote($this->legacyHost, '#').'#i', $href);

            return '<a href="'.e($href).'"'.($external ? ' target="_blank" rel="noopener"' : '').'>'.$inner.'</a>';
        }

        return "<$tag>$inner</$tag>";
    }

    private function tidy(string $html): string
    {
        $html = str_replace("\u{00A0}", ' ', $html);
        $html = preg_replace('#<(p|li|h[2-4]|strong|em)>\s*(?:<br>\s*)*</\1>#', '', $html); // empty elements
        $html = preg_replace('#<p>\s*(<(?:h[2-4]|ul|ol|table|p)\b)#', '$1', $html);       // block wrapped in p
        $html = preg_replace('#(</(?:h[2-4]|ul|ol|table|p)>)\s*</p>#', '$1', $html);
        $html = preg_replace("/\n{2,}/", "\n", $html);
        $html = preg_replace('#>\s+<#', ">\n<", $html);
        $html = trim($html);
        // Loose text without any block element → paragraph
        if ($html !== '' && ! preg_match('#^<(p|h[2-4]|ul|ol|table|blockquote)\b#', $html)) {
            $html = '<p>'.$html.'</p>';
        }

        return $this->relativeLinks($html);
    }
}
