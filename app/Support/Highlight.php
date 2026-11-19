<?php

namespace App\Support;

/**
 * Lets editors highlight words in headings without HTML: "Grow with [[SEO & Performance]]".
 * Input is escaped first, so admins can't inject markup through headings.
 */
class Highlight
{
    public static function render(?string $text, string $class = 'text-gradient'): string
    {
        $escaped = e((string) $text);

        return preg_replace('/\[\[(.+?)\]\]/', '<span class="'.$class.'">$1</span>', $escaped);
    }

    public static function strip(?string $text): string
    {
        return str_replace(['[[', ']]'], '', (string) $text);
    }
}
