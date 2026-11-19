<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitemapImport extends Model
{
    protected $fillable = ['old_url', 'new_url', 'page_type', 'status', 'lastmod'];

    protected $casts = ['lastmod' => 'datetime'];
}
