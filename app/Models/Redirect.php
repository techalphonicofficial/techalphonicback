<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    public const CACHE_KEY = 'redirects.map';

    protected $fillable = ['old_url', 'new_url', 'status_code', 'is_active', 'hits', 'last_hit_at', 'source'];

    protected $casts = ['is_active' => 'boolean', 'last_hit_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (self $r) {
            $r->old_url = static::normalizePath($r->old_url);
        });
        static::saved(function () {
            Cache::forget(self::CACHE_KEY);
            \App\Support\FrontendRevalidator::queue();
        });
        static::deleted(function () {
            Cache::forget(self::CACHE_KEY);
            \App\Support\FrontendRevalidator::queue();
        });
    }

    /** "/Foo/bar" , "https://site.com/foo/bar/" → "/foo/bar/" */
    public static function normalizePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $path = '/'.trim(strtolower(rawurldecode($path)), '/');

        return $path === '/' ? '/' : $path.'/';
    }

    /** @return array<string, array{0: string|null, 1: int, 2: int}> path => [target, code, id] */
    public static function activeMap(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::where('is_active', true)
            ->get(['id', 'old_url', 'new_url', 'status_code'])
            ->mapWithKeys(fn ($r) => [$r->old_url => [$r->new_url, $r->status_code, $r->id]])
            ->all());
    }

    /** Called when a slug changes: add old→new, collapse chains, never create a loop. */
    public static function recordSlugChange(string $oldPath, string $newPath): void
    {
        $oldPath = static::normalizePath($oldPath);
        $newPath = static::normalizePath($newPath);
        if ($oldPath === $newPath) {
            return;
        }

        // The new URL is live again, so any redirect away from it must go.
        static::where('old_url', $newPath)->delete();
        // Point existing redirects that targeted the old URL straight to the new one (no chains).
        static::where('new_url', $oldPath)->update(['new_url' => $newPath]);

        static::updateOrCreate(
            ['old_url' => $oldPath],
            ['new_url' => $newPath, 'status_code' => 301, 'is_active' => true, 'source' => 'slug_change']
        );
    }

    /** Redirects whose destination itself redirects or is empty for a 301/302. */
    public static function brokenCount(): int
    {
        $olds = static::where('is_active', true)->pluck('old_url')->all();

        return static::where('is_active', true)
            ->where(fn ($q) => $q->whereIn('new_url', $olds)->orWhere(fn ($q) => $q->whereNull('new_url')->where('status_code', '!=', 410)))
            ->count();
    }
}
