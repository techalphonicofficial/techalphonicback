<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\UrlSlug;
use Illuminate\Database\Eloquent\Relations\Relation;

class PageController extends Controller
{
    /** Any flat-URL page (CMS page, service hub, programmatic page) by slug. */
    public function show(string $slug)
    {
        $entry = UrlSlug::where('slug', $slug)->firstOrFail();
        $class = Relation::getMorphedModel($entry->routable_type);
        $model = $class::with('visibleBlocks')->find($entry->routable_id);
        abort_unless($model && $model->isPublished(), 404);

        return new PageResource($model);
    }
}
