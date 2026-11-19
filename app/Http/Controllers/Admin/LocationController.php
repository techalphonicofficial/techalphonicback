<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\LocationRequest;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;

class LocationController extends ContentController
{
    protected string $modelClass = Location::class;

    protected string $resource = 'locations';

    protected string $routeName = 'admin.locations';

    protected string $viewPath = 'admin.locations';

    protected string $label = 'Location';

    protected string $labelPlural = 'Locations';

    protected array $searchColumns = ['name', 'state', 'slug'];

    protected array $sortable = ['name', 'sort_order'];

    protected array $columns = [
        ['Name', 'name', 'title'],
        ['Type', 'type', 'badge'],
        ['State', 'state', 'text'],
        ['Country', 'country', 'text'],
        ['SEO pages', 'landing_pages_count', 'text'],
        ['Status', 'status', 'badge'],
    ];

    protected function query()
    {
        return Location::query()->withCount('landingPages');
    }

    protected function requestClass(): string
    {
        return LocationRequest::class;
    }

    protected function defaults(): array
    {
        return ['status' => 'published', 'type' => 'city', 'country' => 'India'];
    }

    protected function formData(Model $item): array
    {
        return ['parents' => Location::whereKeyNot($item->id)->whereIn('type', ['state', 'country', 'region'])->orderBy('name')->pluck('name', 'id')];
    }
}
