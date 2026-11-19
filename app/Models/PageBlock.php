<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PageBlock extends Model
{
    use FlushesContentCache;

    /** type => label. Each type has a partial at resources/views/blocks/{type}.blade.php */
    public const TYPES = [
        'hero' => 'Hero',
        'text' => 'Text',
        'image_text' => 'Image + Text',
        'two_column' => 'Two Column',
        'three_column' => 'Three Column',
        'service_cards' => 'Service Cards',
        'feature_cards' => 'Feature Cards',
        'benefits' => 'Benefits',
        'statistics' => 'Statistics',
        'process' => 'Process',
        'timeline' => 'Timeline',
        'testimonials' => 'Testimonials',
        'case_studies' => 'Case Studies',
        'industries' => 'Industries',
        'technologies' => 'Technologies',
        'pricing' => 'Pricing',
        'faq' => 'FAQ',
        'cta' => 'Call to Action',
        'logo_cloud' => 'Logo Cloud',
        'video' => 'Video',
        'gallery' => 'Gallery',
        'comparison_table' => 'Comparison Table',
        'custom_html' => 'Custom HTML',
        'related_services' => 'Related Services',
        'related_blogs' => 'Related Blogs',
        'related_locations' => 'Related Locations',
        'contact_form' => 'Contact Form',
    ];

    protected $fillable = ['type', 'data', 'sort_order', 'is_visible'];

    protected $casts = ['data' => 'array', 'is_visible' => 'boolean'];

    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }
}
