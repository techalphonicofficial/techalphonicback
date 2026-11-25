<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Location extends Model
{
    use FlushesContentCache, HasFactory, SoftDeletes;

    /** Hierarchy: country → (state / region) → city → area. An area is a locality inside one city, e.g. "Sector 62" in Noida. */
    public const TYPES = ['city' => 'City', 'area' => 'Area (inside a city)', 'state' => 'State', 'country' => 'Country', 'region' => 'Region'];

    protected $fillable = [
        'parent_id', 'name', 'slug', 'type', 'state', 'country', 'description', 'hero_image_id', 'latitude', 'longitude',
        'address', 'postal_code', 'phone', 'email', 'business_info', 'local_facts', 'seo_title', 'seo_description',
        'status', 'sort_order',
    ];

    protected $casts = ['business_info' => 'array', 'local_facts' => 'array', 'latitude' => 'float', 'longitude' => 'float'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Localities inside this city. */
    public function areas(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('type', 'area')->orderBy('sort_order')->orderBy('name');
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    public function heroImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_image_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'published');
    }

    public function isArea(): bool
    {
        return $this->type === 'area';
    }

    /** The published city an area belongs to (null for every other type). */
    public function city(): ?self
    {
        return $this->isArea() && $this->parent?->status === 'published' ? $this->parent : null;
    }

    /** "Sector 62, Noida" for areas; the plain name for every other type. */
    public function getDisplayNameAttribute(): string
    {
        return $this->isArea() && $this->parent ? $this->name.', '.$this->parent->name : (string) $this->name;
    }

    /**
     * Public URL of a service in this location: its published Service + Location page, else the automatic
     * /location/{service}/{location}/ page.
     *
     * @param  Collection<int, LandingPage>|null  $pages  this location's published pages, when already loaded
     */
    public function urlFor(?Service $service, ?Collection $pages = null): string
    {
        if (! $service) {
            return slash_url('/location/'.$this->slug);
        }
        $page = $pages
            ? $pages->first(fn ($p) => $p->service_id === $service->id && ! $p->industry_id)
            : $this->landingPages()->published()->where('service_id', $service->id)->whereNull('industry_id')->first();

        return $page ? $page->url : slash_url('/location/'.$service->slug.'/'.$this->slug);
    }
}
