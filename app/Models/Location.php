<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use FlushesContentCache, HasFactory, SoftDeletes;

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
}
