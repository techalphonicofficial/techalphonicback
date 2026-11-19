@extends('admin.content.form', ['tabs' => ['basic' => 'Industry', 'details' => 'Challenges & solutions', 'faqs' => 'FAQs']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="name" label="Industry name" :value="$item->name" required />
    <x-admin.field name="plural_name" label="Plural / audience label" :value="$item->plural_name" help="e.g. 'Real Estate Companies' — used in “SEO for …” headings." />
    <div x-data="slugField(@js(old('slug', $item->slug)), 'name')">
        <label class="a-label" for="slug">Slug</label>
        <input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input">
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    <x-admin.field type="select" name="icon" label="Icon" :value="$item->icon" :options="array_combine(\App\Support\Icons::names(), \App\Support\Icons::names())" />
    <x-admin.field type="textarea" name="description" label="Description" :value="$item->description" rows="4" class="md:col-span-2" />
    <x-admin.media-picker name="image_id" label="Image" :media="$item->image" />
    <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
    <x-admin.field name="seo_title" label="Default SEO title" :value="$item->seo_title" />
    <x-admin.field name="seo_description" label="Default SEO description" :value="$item->seo_description" />
</div>
@endsection

@section('tab-details')
<div class="grid gap-6">
    <x-admin.repeater name="challenges" label="Challenges" :rows="$item->challenges ?? []" :fields="['title' => 'Challenge']" />
    <x-admin.repeater name="solutions" label="Our solutions" :rows="$item->solutions ?? []" :fields="['title' => 'Solution']" />
    <x-admin.repeater name="benefits" label="Benefits" :rows="$item->benefits ?? []" :fields="['title' => 'Benefit', 'text' => 'Detail']" />
</div>
@endsection
