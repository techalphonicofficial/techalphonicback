<?php

namespace App\Http\Controllers;


class RobotsController extends Controller
{
    public function __invoke()
    {
        if (filter_var(setting('discourage_indexing'), FILTER_VALIDATE_BOOLEAN) || app()->environment('staging')) {
            $body = "User-agent: *\nDisallow: /\n";
        } else {
            $body = str_replace('{sitemap}', route('sitemap'), (string) setting('robots_txt'));
            if (! str_contains($body, 'Sitemap:')) {
                $body .= "\n\nSitemap: ".route('sitemap');
            }
        }

        return response(trim($body)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
