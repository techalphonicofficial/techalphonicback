<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    use FlushesContentCache;

    protected $table = 'seo_meta';

    protected $fillable = [
        'meta_title', 'meta_description', 'meta_keywords', 'canonical_url', 'robots_index', 'robots_follow',
        'og_title', 'og_description', 'og_image_id', 'twitter_title', 'twitter_description', 'twitter_image_id',
        'schema_type', 'schema_json', 'schema_enabled', 'focus_keyword', 'secondary_keywords',
        'include_in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',
        'schema_enabled' => 'boolean',
        'include_in_sitemap' => 'boolean',
        'sitemap_priority' => 'float',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }

    public function twitterImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'twitter_image_id');
    }

    public function robotsContent(): string
    {
        return ($this->robots_index ? 'index' : 'noindex').', '.($this->robots_follow ? 'follow' : 'nofollow');
    }
}
