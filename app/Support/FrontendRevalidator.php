<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tells the Next.js frontend to drop its cached pages after an admin change. Called from ContentCache::flush()
 * and redirect saves; runs once per request, after the response is sent, and never breaks the admin save.
 */
class FrontendRevalidator
{
    private static bool $queued = false;

    public static function queue(): void
    {
        $url = config('services.frontend.url');
        $secret = config('services.frontend.revalidate_secret');
        if (! $url || ! $secret || self::$queued || app()->runningUnitTests()) {
            return;
        }
        self::$queued = true;

        app()->terminating(function () use ($url, $secret) {
            self::$queued = false;
            try {
                Http::timeout(3)->post(rtrim($url, '/').'/next-api/revalidate', ['secret' => $secret, 'tag' => 'site']);
            } catch (\Throwable $e) {
                Log::warning('Frontend revalidation failed: '.$e->getMessage());
            }
        });
    }

    /** Admin → Maintenance: purge the Next.js page cache right now and report the outcome. */
    public static function revalidateNow(): array
    {
        return self::post('revalidate', ['tag' => 'site']);
    }

    /** Admin → Maintenance: the URLs and build the live frontend runs with (see next-api/status). */
    public static function status(): array
    {
        return self::post('status');
    }

    /** @return array{ok: bool, status: int, error: ?string, data: array} */
    private static function post(string $endpoint, array $payload = []): array
    {
        $url = rtrim((string) config('services.frontend.url'), '/');
        $secret = (string) config('services.frontend.revalidate_secret');
        if ($url === '' || $secret === '') {
            return ['ok' => false, 'status' => 0, 'error' => 'FRONTEND_URL or FRONTEND_REVALIDATE_SECRET is not set in backend/.env.', 'data' => []];
        }

        try {
            $res = Http::timeout(10)->acceptJson()->post($url.'/next-api/'.$endpoint, ['secret' => $secret] + $payload);
            $error = match (true) {
                $res->successful() => null,
                $res->status() === 401 => 'Rejected: REVALIDATE_SECRET in the frontend does not match FRONTEND_REVALIDATE_SECRET.',
                $res->status() === 404 => 'Not found: the frontend build is older than this feature — rebuild and restart it.',
                default => 'Frontend answered HTTP '.$res->status().'.',
            };

            return ['ok' => $res->successful(), 'status' => $res->status(), 'error' => $error, 'data' => (array) $res->json()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'error' => 'Could not reach '.$url.': '.$e->getMessage(), 'data' => []];
        }
    }
}
