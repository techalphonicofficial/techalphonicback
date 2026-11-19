<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroSlide extends Model
{
    use FlushesContentCache;

    protected $fillable = [
        'heading', 'subheading', 'description', 'cta_text', 'cta_url', 'secondary_cta_text', 'secondary_cta_url',
        'image_id', 'background_id', 'background_video_id', 'video_url', 'badge', 'trust_text', 'show_form', 'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean', 'show_form' => 'boolean'];

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    /** Poster / fallback image behind the hero. */
    public function background(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'background_id');
    }

    /** Muted, looping MP4 played behind the hero on capable connections. */
    public function backgroundVideo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'background_video_id');
    }

    /** "Watch video" target: YouTube link or MP4 URL; falls back to the background video. */
    public function showreelUrl(): ?string
    {
        return $this->video_url ?: $this->backgroundVideo?->url;
    }
}
