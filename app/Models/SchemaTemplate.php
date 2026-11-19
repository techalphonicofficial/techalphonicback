<?php

namespace App\Models;

use App\Models\Concerns\FlushesContentCache;
use Illuminate\Database\Eloquent\Model;

class SchemaTemplate extends Model
{
    use FlushesContentCache;

    protected $table = 'schemas';

    protected $fillable = ['name', 'type', 'json_template', 'applies_to', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public const TYPES = [
        'Organization', 'LocalBusiness', 'ProfessionalService', 'WebSite', 'WebPage', 'Service', 'Product', 'Article',
        'BlogPosting', 'BreadcrumbList', 'FAQPage', 'Review', 'AggregateRating', 'ContactPage', 'AboutPage', 'CollectionPage',
    ];

    public const APPLIES_TO = ['global', 'page', 'service', 'service_category', 'landing_page', 'blog', 'portfolio', 'case_study'];
}
