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

class ServiceCategory extends Model
{
    use FlushesContentCache, HasBlocks, HasFactory, HasFaqs, HasInternalLinks, HasPublishing, HasSeo, HasSlugRoute, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'menu_label', 'h1', 'short_description', 'description', 'icon', 'image_id', 'accent',
        'status', 'published_at', 'sort_order',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('sort_order');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function getHeadingAttribute(): string
    {
        return $this->h1 ?: $this->name;
    }
}
