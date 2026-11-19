<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlugRoute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Portfolio extends Model
{
    use FlushesContentCache, HasFactory, HasPublishing, HasSeo, HasSlugRoute, SoftDeletes;

    protected bool $usesSlugRegistry = false;

    protected string $urlPrefix = 'portfolio';

    protected $fillable = [
        'title', 'slug', 'client', 'category', 'summary', 'description', 'featured_image_id', 'gallery', 'technologies',
        'industry_id', 'service_id', 'results', 'project_url', 'status', 'published_at', 'sort_order',
    ];

    protected $casts = ['gallery' => 'array', 'technologies' => 'array'];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function galleryMedia()
    {
        return Media::whereIn('id', $this->gallery ?? [])->get();
    }

    public function getHeadingAttribute(): string
    {
        return $this->title;
    }
}
