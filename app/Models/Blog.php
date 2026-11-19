<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasInternalLinks;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlugRoute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use FlushesContentCache, HasFactory, HasFaqs, HasInternalLinks, HasPublishing, HasSeo, HasSlugRoute, SoftDeletes;

    protected bool $usesSlugRegistry = false;

    protected string $urlPrefix = 'blog';

    protected $fillable = [
        'blog_category_id', 'author_id', 'title', 'slug', 'excerpt', 'content', 'featured_image_id', 'reading_time',
        'show_toc', 'faq_enabled', 'is_featured', 'status', 'published_at',
    ];

    protected $casts = ['show_toc' => 'boolean', 'faq_enabled' => 'boolean', 'is_featured' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $blog) {
            $words = str_word_count(strip_tags((string) $blog->content));
            $blog->reading_time = max(1, (int) ceil($words / 220));
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_tag');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'blog_service');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function getHeadingAttribute(): string
    {
        return $this->title;
    }
}
