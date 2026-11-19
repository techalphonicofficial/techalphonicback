<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Adds id="" anchors to H2/H3 in rich content and returns a nested table of contents.
 */
class TocService
{
    /** @return array{html: string, toc: array<int, array{id: string, text: string, level: int}>} */
    public function process(?string $html): array
    {
        $html = (string) $html;
        $toc = [];
        $used = [];

        $html = preg_replace_callback('/<h([23])([^>]*)>(.*?)<\/h\1>/is', function ($m) use (&$toc, &$used) {
            $level = (int) $m[1];
            $attrs = $m[2];
            $text = trim(html_entity_decode(strip_tags($m[3])));
            if ($text === '') {
                return $m[0];
            }

            if (preg_match('/\bid=["\']([^"\']+)["\']/', $attrs, $idMatch)) {
                $id = $idMatch[1];
            } else {
                $base = Str::slug(Str::limit($text, 60, '')) ?: 'section';
                $id = $base;
                $n = 2;
                while (isset($used[$id])) {
                    $id = $base.'-'.$n++;
                }
                $attrs .= ' id="'.$id.'"';
            }
            $used[$id] = true;
            $toc[] = ['id' => $id, 'text' => $text, 'level' => $level];

            return "<h{$level}{$attrs}>{$m[3]}</h{$level}>";
        }, $html);

        return ['html' => $html, 'toc' => $toc];
    }
}
