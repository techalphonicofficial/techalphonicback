<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\LocationRequest;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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

    protected array $listWith = ['parent:id,name'];

    protected array $columns = [
        ['Name', 'name', 'title'],
        ['Type', 'type', 'badge'],
        ['Parent', 'parent.name', 'text'],
        ['Areas', 'areas_count', 'text'],
        ['State', 'state', 'text'],
        ['Country', 'country', 'text'],
        ['SEO pages', 'landing_pages_count', 'text'],
        ['Status', 'status', 'badge'],
    ];

    protected function query()
    {
        return Location::query()->withCount(['landingPages', 'areas']);
    }

    protected function requestClass(): string
    {
        return LocationRequest::class;
    }

    protected function defaults(): array
    {
        return ['status' => 'published', 'type' => 'city', 'country' => 'India'];
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($city = $request->integer('city')) {
            $query->where('parent_id', $city);
        }
    }

    protected function filterOptions(): array
    {
        return [
            'type' => ['label' => 'Type', 'options' => Location::TYPES],
            'city' => ['label' => 'Areas of city', 'options' => Location::where('type', 'city')->orderBy('name')->pluck('name', 'id')->all()],
        ];
    }

    protected function formData(Model $item): array
    {
        $parents = Location::whereKeyNot($item->id)->orderBy('name');

        return [
            'parents' => (clone $parents)->whereIn('type', ['state', 'country', 'region'])->pluck('name', 'id'),
            'cities' => (clone $parents)->where('type', 'city')->pluck('name', 'id'),
        ];
    }
}
