<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UrlSlug extends Model
{
    protected $fillable = ['slug', 'routable_type', 'routable_id'];

    public function routable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Words that can never be a flat slug because a fixed route already owns them. */
    public const RESERVED = [
        'admin', 'login', 'logout', 'dashboard', 'api', 'blog', 'portfolio', 'case-studies', 'search', 'preview',
        'sitemap', 'sitemap.xml', 'robots.txt', 'storage', 'build', 'enquiry', 'lead', 'password', 'register',
        'industries', 'locations', 'services', 'up', 'vendor', 'livewire', 'feed',
    ];

    public static function isAvailable(string $slug, ?string $type = null, ?int $id = null): bool
    {
        if (in_array($slug, self::RESERVED, true)) {
            return false;
        }

        return ! static::where('slug', $slug)
            ->when($type && $id, fn ($q) => $q->where(fn ($q) => $q->where('routable_type', '!=', $type)->orWhere('routable_id', '!=', $id)))
            ->exists();
    }
}
