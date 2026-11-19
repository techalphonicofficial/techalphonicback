<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    use FlushesContentCache;

    /** type => morph alias of the linked model (null for custom URL) */
    public const TYPES = [
        'custom' => null,
        'page' => 'page',
        'service' => 'service',
        'service_category' => 'service_category',
        'landing_page' => 'landing_page',
        'blog' => 'blog',
        'heading' => null, // mega menu column heading, no link
    ];

    protected $fillable = [
        'menu_id', 'parent_id', 'label', 'type', 'linkable_type', 'linkable_id', 'url', 'icon', 'description',
        'is_mega', 'open_new_tab', 'is_active', 'sort_order',
    ];

    protected $casts = ['is_mega' => 'boolean', 'open_new_tab' => 'boolean', 'is_active' => 'boolean'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function href(): ?string
    {
        if ($this->type === 'heading') {
            return null;
        }
        if ($this->linkable_type && $this->linkable) {
            return $this->linkable->url;
        }
        if (! $this->url) {
            return null;
        }

        return preg_match('#^(https?:|mailto:|tel:|\#)#', $this->url) ? $this->url : slash_url($this->url);
    }
}
