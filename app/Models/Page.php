<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use App\Models\Concerns\HasBlocks;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasInternalLinks;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlugRoute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use FlushesContentCache, HasBlocks, HasFactory, HasFaqs, HasInternalLinks, HasPublishing, HasSeo, HasSlugRoute, SoftDeletes;

    public const TEMPLATES = [
        'default' => 'Default (blocks)',
        'packages' => 'Packages & Pricing',
        'about' => 'About',
        'contact' => 'Contact',
        'legal' => 'Legal / long text',
        'hub' => 'Hub (lists child pages)',
    ];

    public const HERO_LAYOUTS = ['split' => 'Split (text + image)', 'centered' => 'Centered', 'minimal' => 'Minimal', 'image-bg' => 'Background image', 'dark' => 'Dark (bold)'];

    protected $fillable = [
        'parent_id', 'title', 'slug', 'template', 'h1', 'hero_layout', 'hero_subheading', 'excerpt', 'content',
        'featured_image_id', 'author_id', 'show_in_search', 'status', 'published_at', 'sort_order',
    ];

    protected $casts = ['show_in_search' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getHeadingAttribute(): string
    {
        return $this->h1 ?: $this->title;
    }
}
