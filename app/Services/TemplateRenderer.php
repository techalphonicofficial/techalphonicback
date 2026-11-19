<?php

namespace App\Services;

/**
 * Replaces {{variable}} placeholders in strings / nested arrays.
 * Unknown variables are left visible in admin preview but stripped on render so no "{{foo}}" leaks to Google.
 */
class TemplateRenderer
{
    public const VARIABLES = [
        'service_name', 'service_name_lower', 'location_name', 'state_name', 'country_name', 'industry_name',
        'industry_plural', 'site_name', 'phone', 'year',
    ];

    public function render(mixed $value, array $vars, bool $stripUnknown = true): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->render($v, $vars, $stripUnknown), $value);
        }
        if (! is_string($value) || ! str_contains($value, '{{')) {
            return $value;
        }

        $out = preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/i', function ($m) use ($vars, $stripUnknown) {
            $key = strtolower($m[1]);

            return array_key_exists($key, $vars) ? (string) $vars[$key] : ($stripUnknown ? '' : $m[0]);
        }, $value);

        // Collapse doubled spaces left by empty variables ("SEO for  in Noida").
        return preg_replace('/ {2,}/', ' ', $out);
    }
}
