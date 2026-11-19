<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    use FlushesContentCache;

    public const KEYS = [
        'hero' => 'Hero',
        'clients' => 'Client logos / trust bar',
        'services' => 'Services',
        'promo' => 'Offer banner',
        'showreel' => 'Video showcase',
        'about' => 'About',
        'stats' => 'Statistics',
        'industries' => 'Industries',
        'technologies' => 'Technologies',
        'process' => 'Process',
        'portfolio' => 'Portfolio & case studies',
        'testimonials' => 'Testimonials',
        'blogs' => 'Latest blogs',
        'faq' => 'FAQ',
        'enquiry' => 'Enquiry form (contact section)',
        'cta' => 'Call to action',
    ];

    protected $fillable = ['key', 'title', 'subtitle', 'eyebrow', 'content', 'sort_order', 'is_visible'];

    protected $casts = ['content' => 'array', 'is_visible' => 'boolean'];

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->content, $key, $default);
    }
}
