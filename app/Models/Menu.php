<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use FlushesContentCache;

    public const LOCATIONS = [
        'main' => 'Main header menu',
        'mobile' => 'Mobile menu (falls back to main)',
        'footer_company' => 'Footer — Company',
        'footer_services' => 'Footer — Services',
        'footer_development' => 'Footer — Development',
        'footer_seo' => 'Footer — SEO',
        'footer_industries' => 'Footer — Industries',
        'footer_resources' => 'Footer — Resources',
        'legal' => 'Footer bottom (legal)',
    ];

    protected $fillable = ['name', 'location', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }
}
