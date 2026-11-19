@extends('admin.content.form', ['tabs' => ['basic' => 'Project', 'content' => 'Description', 'seo' => 'SEO']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="title" label="Project title" :value="$item->title" required class="md:col-span-2" />
    <div class="md:col-span-2" x-data="slugField(@js(old('slug', $item->slug)), 'title')">
        <label class="a-label" for="slug">Slug</label>
        <div class="flex items-center rounded-lg border border-line bg-mist text-sm"><span class="px-3 text-slate-500">{{ url('/portfolio') }}/</span><input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input rounded-l-none border-0"></div>
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    <x-admin.field name="client" label="Client" :value="$item->client" />
    <div>
        <x-admin.field name="category" label="Category" :value="$item->category" list="portfolio-cats" help="e.g. Website, Mobile App, SEO Campaign" />
        <datalist id="portfolio-cats">@foreach($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
    </div>
    <x-admin.field type="select" name="industry_id" label="Industry" :value="$item->industry_id" :options="$industries" />
    <x-admin.field type="select" name="service_id" label="Service" :value="$item->service_id" :options="$services" />
    <x-admin.field type="url" name="project_url" label="Live URL" :value="$item->project_url" />
    <x-admin.field name="technologies_text" label="Technologies" :value="implode(', ', $item->technologies ?? [])" help="Comma separated" />
    <x-admin.field type="textarea" name="summary" label="Summary" :value="$item->summary" rows="3" class="md:col-span-2" />
    <x-admin.field type="textarea" name="results" label="Results" :value="$item->results" rows="3" class="md:col-span-2" />
    <x-admin.media-picker name="featured_image_id" label="Featured image" :media="$item->featuredImage" />
    <x-admin.media-picker name="gallery" label="Gallery" :media="$item->exists ? $item->galleryMedia() : collect()" multiple />
    <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
</div>
@endsection

@section('tab-content')
<x-admin.field type="textarea" name="description" label="Project description" :value="$item->description" editor rows="14" />
@endsection
