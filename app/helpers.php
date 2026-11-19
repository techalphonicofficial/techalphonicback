<?php

use App\Services\SettingsService;

if (! function_exists('slash_url')) {
    /**
     * Absolute URL with the site's canonical trailing slash. Laravel's url()/route() strip it,
     * which would cause a 301 hop on every internal link.
     */
    function slash_url(string $path = '/', array $query = []): string
    {
        if (preg_match('#^(https?:)?//#', $path) && parse_url($path, PHP_URL_HOST) !== parse_url(config('app.url'), PHP_URL_HOST)) {
            return $path; // external
        }
        $parts = parse_url($path);
        $p = $parts['path'] ?? '/';
        $url = rtrim(url($p), '/');
        if (! str_contains(basename($p), '.')) {
            $url .= '/';
        }
        $existing = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $existing);
        }
        $qs = array_merge($existing, $query);

        return $url.($qs ? '?'.http_build_query($qs) : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}

if (! function_exists('sroute')) {
    /** route() with canonical trailing slash. */
    function sroute(string $name, mixed $parameters = [], array $query = []): string
    {
        return slash_url(route($name, $parameters, false), $query);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}
