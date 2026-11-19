<?php

namespace App\Models\Concerns;

use App\Models\Redirect;
use App\Models\UrlSlug;
use Illuminate\Support\Facades\Cache;

/**
 * Keeps the url_slugs registry in sync for models served at flat URLs (/{slug}/)
 * and records a 301 redirect whenever a public URL changes.
 *
 * Models with prefixed URLs (blog, portfolio) set $usesSlugRegistry = false but still get redirects.
 */
trait HasSlugRoute
{
    public static function bootHasSlugRoute(): void
    {
        static::saved(function ($model) {
            if ($model->usesSlugRegistry()) {
                UrlSlug::updateOrCreate(
                    ['routable_type' => $model->getMorphClass(), 'routable_id' => $model->getKey()],
                    ['slug' => $model->slug]
                );
                Cache::forget('slug:'.$model->slug);
            }

            if ($model->wasChanged('slug') && $model->getOriginal('slug')) {
                $old = $model->publicPathFor($model->getOriginal('slug'));
                $new = $model->publicPathFor($model->slug);
                Cache::forget('slug:'.$model->getOriginal('slug'));
                Redirect::recordSlugChange($old, $new);
            }
        });

        static::deleted(function ($model) {
            Cache::forget('slug:'.$model->slug);
            // Soft-deleted rows keep their slug reserved so a restore works; force deletes free it.
            $hardDelete = ! method_exists($model, 'isForceDeleting') || $model->isForceDeleting();
            if ($model->usesSlugRegistry() && $hardDelete) {
                UrlSlug::where('routable_type', $model->getMorphClass())->where('routable_id', $model->getKey())->delete();
            }
        });
    }

    public function usesSlugRegistry(): bool
    {
        return property_exists($this, 'usesSlugRegistry') ? $this->usesSlugRegistry : true;
    }

    /** Relative public path for a given slug, always with trailing slash. */
    public function publicPathFor(string $slug): string
    {
        $prefix = property_exists($this, 'urlPrefix') ? trim($this->urlPrefix, '/') : '';

        return '/'.($prefix ? $prefix.'/' : '').$slug.'/';
    }

    public function getUrlAttribute(): string
    {
        return slash_url($this->publicPathFor($this->slug));
    }

    public function urlSlug()
    {
        return $this->morphOne(UrlSlug::class, 'routable');
    }
}
