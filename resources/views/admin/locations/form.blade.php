@extends('admin.content.form', ['tabs' => ['basic' => 'Location', 'local' => 'Local business & content']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="name" label="Location name" :value="$item->name" required />
    <div x-data="slugField(@js(old('slug', $item->slug)), 'name')">
        <label class="a-label" for="slug">Slug</label>
        <input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input">
        <p class="a-help">Used in programmatic URLs, e.g. seo-company-in-<strong x-text="slug || 'noida'"></strong></p>
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    <x-admin.field type="select" name="type" label="Type" :value="$item->type" :options="['city' => 'City', 'state' => 'State', 'country' => 'Country', 'region' => 'Region']" :empty="false" />
    <x-admin.field type="select" name="parent_id" label="Parent (state / country)" :value="$item->parent_id" :options="$parents" />
    <x-admin.field name="state" label="State" :value="$item->state" />
    <x-admin.field name="country" label="Country" :value="$item->country ?? 'India'" required />
    <x-admin.field type="number" name="latitude" label="Latitude" :value="$item->latitude" step="0.0000001" />
    <x-admin.field type="number" name="longitude" label="Longitude" :value="$item->longitude" step="0.0000001" />
    <x-admin.field type="textarea" name="description" label="Description" :value="$item->description" rows="4" class="md:col-span-2" />
    <x-admin.media-picker name="hero_image_id" label="Hero image (used by generated pages)" :media="$item->heroImage" />
    <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
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
