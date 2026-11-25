<?php

namespace App\Models;

use App\Enums\LandingPageType;
use App\Models\Concerns\FlushesContentCache;
use App\Models\Concerns\HasBlocks;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasInternalLinks;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlugRoute;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Programmatic SEO page: Service + Location, Service + Industry, or Service + Location + Industry.
 * One table instead of three — see docs/ARCHITECTURE.md §3.
 */
class LandingPage extends Model
{
    use FlushesContentCache, HasBlocks, HasFactory, HasFaqs, HasInternalLinks, HasPublishing, HasSeo, HasSlugRoute, SoftDeletes;

    protected $fillable = [
        'page_type', 'service_id', 'location_id', 'industry_id', 'title', 'slug', 'h1', 'hero_layout', 'hero_subheading',
        'introduction', 'content', 'sections', 'benefits', 'process', 'why_choose_us', 'cta', 'show_testimonials',
        'show_industries', 'featured_image_id', 'faq_enabled', 'faq_title', 'generated_by', 'created_by',
        'status', 'published_at',
    ];

    protected $casts = [
        'page_type' => LandingPageType::class,
        'sections' => 'array',
        'benefits' => 'array',
        'process' => 'array',
        'why_choose_us' => 'array',
        'cta' => 'array',
        'faq_enabled' => 'boolean',
        'show_testimonials' => 'boolean',
        'show_industries' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function aiGenerations(): MorphMany
    {
        return $this->morphMany(AiGeneration::class, 'subject');
    }

    public function latestAiGeneration(): MorphOne
    {
        return $this->morphOne(AiGeneration::class, 'subject')->latestOfMany();
    }

    public function scopeServiceLocation(Builder $q): Builder
    {
        return $q->where('page_type', LandingPageType::ServiceLocation->value);
    }

    public function scopeServiceIndustry(Builder $q): Builder
    {
        return $q->where('page_type', LandingPageType::ServiceIndustry->value);
    }

    public function scopeServiceLocationIndustry(Builder $q): Builder
    {
        return $q->where('page_type', LandingPageType::ServiceLocationIndustry->value);
    }

    /** Variables available in {{...}} placeholders for this page. */
    public function templateVariables(): array
    {
        $settings = app(SettingsService::class);

        return [
            'service_name' => $this->service?->name ?? '',
            'service_name_lower' => mb_strtolower($this->service?->name ?? ''),
            'location_name' => $this->location?->display_name ?? '',
            'state_name' => $this->location?->state ?? '',
            'country_name' => $this->location?->country ?? '',
            'industry_name' => $this->industry?->name ?? '',
            'industry_plural' => $this->industry?->plural_name ?: ($this->industry?->name ?? ''),
            'site_name' => $settings->get('site_name', config('app.name')),
            'phone' => $settings->get('phone', ''),
            'year' => now()->year,
        ];
    }

    public function getHeadingAttribute(): string
    {
        return $this->h1 ?: $this->title;
    }
}
