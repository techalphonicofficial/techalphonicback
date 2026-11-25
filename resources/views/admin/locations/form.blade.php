@extends('admin.content.form', ['tabs' => ['basic' => 'Location'] + ($item->type === 'city' ? ['areas' => 'Areas in this city'] : []) + ['local' => 'Local business & content']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2" x-data="{ type: @js(old('type', $item->type ?? 'city')) }">
    <x-admin.field name="name" label="Location name" :value="$item->name" required />
    <div x-data="slugField(@js(old('slug', $item->slug)), 'name')">
        <label class="a-label" for="slug">Slug</label>
        <input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input">
        <p class="a-help">Used in programmatic URLs, e.g. seo-company-in-<strong x-text="slug || 'noida'"></strong></p>
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    <x-admin.field type="select" name="type" label="Type" :value="$item->type" :options="\App\Models\Location::TYPES" :empty="false" x-model="type" help="Area = a locality inside a city (e.g. Sector 62 in Noida). It is listed on that city's pages." />
    {{-- Same field, two lists: only the enabled select is submitted. --}}
    <div x-show="type !== 'area'">
        <x-admin.field type="select" name="parent_id" label="Parent (state / country)" :value="$item->parent_id" :options="$parents" ::disabled="type === 'area'" />
    </div>
    <div x-show="type === 'area'" x-cloak>
        <x-admin.field type="select" name="parent_id" label="City" :value="$item->parent_id" :options="$cities" ::disabled="type !== 'area'" ::required="type === 'area'" help="State and country are taken from the city." />
    </div>
    <div x-show="type !== 'area'"><x-admin.field name="state" label="State" :value="$item->state" /></div>
    <div x-show="type !== 'area'"><x-admin.field name="country" label="Country" :value="$item->country ?? 'India'" required /></div>
    <x-admin.field type="number" name="latitude" label="Latitude" :value="$item->latitude" step="0.0000001" />
    <x-admin.field type="number" name="longitude" label="Longitude" :value="$item->longitude" step="0.0000001" />
    <x-admin.field type="textarea" name="description" label="Description" :value="$item->description" rows="4" class="md:col-span-2" />
    <x-admin.media-picker name="hero_image_id" label="Hero image (used by generated pages)" :media="$item->heroImage" />
    <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
</div>
@endsection

@section('tab-areas')
<div class="grid gap-5">
    @if($areas->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="a-table">
                <thead><tr><th>Area</th><th>Slug</th><th>SEO pages</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach($areas as $area)
                        <tr>
                            <td><a href="{{ route('admin.locations.edit', $area->id) }}" class="font-semibold text-ink hover:text-brand">{{ $area->name }}</a></td>
                            <td class="text-slate-600">{{ $area->slug }}</td>
                            <td class="text-slate-600">{{ $area->landing_pages_count }}</td>
                            <td><span class="a-badge bg-slate-100 text-slate-600">{{ $area->status === 'published' ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-right"><a href="{{ route('admin.locations.edit', $area->id) }}" class="a-btn a-btn-light a-btn-sm"><x-icon name="pen" class="size-3" /> Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @can('locations.create')
        <x-admin.field type="textarea" name="new_areas_text" label="Add areas (one per line)" rows="6" help="For example: Sector 18, Sector 62, Sector 63 — each on its own line. They are created when you save and appear under this city on the website." />
    @endcan
</div>
@endsection

@section('tab-local')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="address" label="Local address (if you have an office here)" :value="$item->address" class="md:col-span-2" />
    <x-admin.field name="postal_code" label="PIN code" :value="$item->postal_code" />
    <x-admin.field name="phone" label="Local phone" :value="$item->phone" />
    <x-admin.field type="email" name="email" label="Local email" :value="$item->email" />
    <div></div>
    <x-admin.field type="textarea" name="local_facts_text" label="Local insights (one per line)" :value="implode(PHP_EOL, $item->local_facts ?? [])" rows="5" class="md:col-span-2"
        help="Shown on every programmatic page for this location — business districts, local market notes, areas served. This is what makes location pages genuinely useful." />
    <x-admin.field name="seo_title" label="Default SEO title" :value="$item->seo_title" />
    <x-admin.field name="seo_description" label="Default SEO description" :value="$item->seo_description" />
</div>
@endsection
