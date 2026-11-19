<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Central list of frontend cache keys so admin saves can invalidate them in one place.
 * Uses a version counter so it works on every cache driver (no tag support required).
 */
class ContentCache
{
    public const VERSION_KEY = 'content-cache-version';

    public static function key(string $name): string
    {
        return 'content:v'.Cache::get(self::VERSION_KEY, 1).':'.$name;
    }

    public static function remember(string $name, \Closure $callback, ?int $ttl = 86400): mixed
    {
        return Cache::remember(self::key($name), $ttl, $callback);
    }

    public static function flush(): void
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);
        Cache::forever(self::VERSION_KEY, $version + 1);
        FrontendRevalidator::queue();
    }
}
