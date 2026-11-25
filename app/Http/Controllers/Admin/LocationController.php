<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\LocationRequest;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocationController extends ContentController
{
    protected string $modelClass = Location::class;

    protected string $resource = 'locations';

    protected string $routeName = 'admin.locations';

    protected string $viewPath = 'admin.locations';

    protected string $label = 'Location';

    protected string $labelPlural = 'Locations';

    protected array $searchColumns = ['name', 'state', 'slug'];

    protected array $nonFillable = ['new_areas_text'];

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
        // "Add area" links open the form with the type and city already chosen.
        $type = request()->query('type');

        return [
            'status' => 'published',
            'type' => is_string($type) && isset(Location::TYPES[$type]) ? $type : 'city',
            'parent_id' => request()->integer('parent_id') ?: null,
            'country' => 'India',
        ];
    }

    /** City screen → "Areas" tab: one new area per line. Names the city already has are skipped. */
    protected function afterSave(Model $item, FormRequest $request): void
    {
        $names = collect(preg_split('/\r?\n/', (string) $request->input('new_areas_text')))
            ->map(fn ($n) => trim($n))->filter(fn ($n) => Str::slug($n) !== '')->unique(fn ($n) => Str::slug($n));
        if ($item->type !== 'city' || $names->isEmpty() || ! $request->user()->can('locations.create')) {
            return;
        }

        $existing = $item->areas()->pluck('name')->map(fn ($n) => Str::slug($n))->all();
        $order = (int) $item->areas()->max('sort_order');
        foreach ($names as $name) {
            if (in_array(Str::slug($name), $existing, true)) {
                continue;
            }
            $base = Str::limit(Str::slug($name).'-'.$item->slug, 110, '');
            $slug = $base;
            for ($i = 2; Location::withTrashed()->where('slug', $slug)->exists(); $i++) {
                $slug = $base.'-'.$i;
            }
            Location::create([
                'parent_id' => $item->id, 'type' => 'area', 'name' => $name, 'slug' => $slug,
                'state' => $item->state, 'country' => $item->country, 'sort_order' => ++$order,
                'status' => $request->user()->can('locations.publish') ? 'published' : 'draft',
            ]);
        }
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
            'areas' => $item->exists ? $item->areas()->withCount('landingPages')->get() : collect(),
        ];
    }
}
