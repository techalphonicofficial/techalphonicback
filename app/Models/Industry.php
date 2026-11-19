<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use App\Models\Concerns\HasFaqs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Industry extends Model
{
    use FlushesContentCache, HasFactory, HasFaqs, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'plural_name', 'description', 'icon', 'image_id', 'challenges', 'solutions', 'benefits',
        'seo_title', 'seo_description', 'status', 'sort_order',
    ];

    protected $casts = ['challenges' => 'array', 'solutions' => 'array', 'benefits' => 'array'];

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'published')->orderBy('sort_order');
    }

    /** First published landing page for this industry — used to link industry cards somewhere useful. */
    public function primaryLandingPage(): ?LandingPage
    {
        return $this->landingPages()->published()->whereNull('location_id')->orderBy('id')->first();
    }
}
