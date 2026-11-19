<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;

class SitemapController extends Controller
{
    public function __construct(private SitemapService $sitemaps) {}

    public function index()
    {
        return response()->view('sitemap.index', ['sections' => $this->sitemaps->index()], 200, $this->headers());
    }

    public function section(string $section)
    {
        abort_unless(array_key_exists($section, SitemapService::SECTIONS), 404);
        $entries = $this->sitemaps->entries($section);
        abort_if($entries->isEmpty(), 404);

        return response()->view('sitemap.urlset', ['entries' => $entries], 200, $this->headers());
    }

    private function headers(): array
    {
        return ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600', 'X-Robots-Tag' => 'noindex'];
    }
}
