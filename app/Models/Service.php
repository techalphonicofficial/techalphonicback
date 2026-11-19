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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use FlushesContentCache, HasBlocks, HasFactory, HasFaqs, HasInternalLinks, HasPublishing, HasSeo, HasSlugRoute, SoftDeletes;

    protected $fillable = [
        'parent_id', 'service_category_id', 'name', 'slug', 'menu_label', 'short_description', 'description',
        'hero_heading', 'hero_description', 'hero_highlights', 'hero_layout', 'hero_image_id', 'featured_image_id', 'icon', 'content',
        'benefits', 'features', 'process', 'technologies', 'pricing', 'cta_heading', 'cta_description',
        'landing_template', 'is_featured', 'status', 'published_at', 'sort_order',
    ];

    protected $casts = [
        'hero_highlights' => 'array',
        'benefits' => 'array',
        'features' => 'array',
        'process' => 'array',
        'technologies' => 'array',
        'pricing' => 'array',
        'landing_template' => 'array',
        'is_featured' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    public function heroImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_image_id');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class, 'blog_service');
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function caseStudies(): HasMany
    {
        return $this->hasMany(CaseStudy::class);
    }

    public function getHeadingAttribute(): string
    {
        return $this->hero_heading ?: $this->name;
    }
}
