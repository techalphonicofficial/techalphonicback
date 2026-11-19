<?php

namespace App\Models\Concerns;

use App\Support\ContentCache;

/**
 * Any admin save/delete on a content model clears the shared frontend caches (menus, footer, home, sitemap).
 */
trait FlushesContentCache
{
    public static function bootFlushesContentCache(): void
    {
        static::saved(fn () => ContentCache::flush());
        static::deleted(fn () => ContentCache::flush());
    }
}
