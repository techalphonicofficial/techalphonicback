<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    public static function bootHasSeo(): void
    {
        static::forceDeleted(fn ($model) => $model->seo()->delete());
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /** Persist SEO fields from a validated request array (keys prefixed "seo."). */
    public function syncSeo(array $data): SeoMeta
    {
        return $this->seo()->updateOrCreate([], $data);
    }

    public function isIndexable(): bool
    {
        return ! $this->seo || ($this->seo->robots_index && $this->seo->include_in_sitemap !== false);
    }
}
