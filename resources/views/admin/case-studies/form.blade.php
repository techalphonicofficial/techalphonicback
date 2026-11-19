@extends('admin.content.form', ['tabs' => ['basic' => 'Case study', 'story' => 'Story', 'seo' => 'SEO']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="title" label="Title" :value="$item->title" required class="md:col-span-2" />
    <div class="md:col-span-2" x-data="slugField(@js(old('slug', $item->slug)), 'title')">
        <label class="a-label" for="slug">Slug</label>
        <div class="flex items-center rounded-lg border border-line bg-mist text-sm"><span class="px-3 text-slate-500">{{ url('/case-studies') }}/</span><input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input rounded-l-none border-0"></div>
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    <x-admin.field name="client" label="Client" :value="$item->client" />
    <x-admin.field type="select" name="industry_id" label="Industry" :value="$item->industry_id" :options="$industries" />
    <x-admin.field type="select" name="service_id" label="Service" :value="$item->service_id" :options="$services" />
    <x-admin.field type="select" name="testimonial_id" label="Client testimonial" :value="$item->testimonial_id" :options="$testimonials" />
    <x-admin.field type="textarea" name="summary" label="Summary" :value="$item->summary" rows="3" class="md:col-span-2" />
    <x-admin.field name="technologies_text" label="Technologies" :value="implode(', ', $item->technologies ?? [])" help="Comma separated" class="md:col-span-2" />
    <div class="md:col-span-2"><x-admin.repeater name="metrics" label="Key results (metrics)" :rows="$item->metrics ?? []" :fields="['value' => 'Value (e.g. +240%)', 'label' => 'Label (e.g. organic traffic)']" /></div>
    <x-admin.media-picker name="featured_image_id" label="Featured image" :media="$item->featuredImage" />
    <x-admin.media-picker name="gallery" label="Gallery" :media="$item->exists ? $item->galleryMedia() : collect()" multiple />
    <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
</div>
@endsection

@section('tab-story')
<div class="grid gap-4">
    <x-admin.field type="textarea" name="challenge" label="Challenge" :value="$item->challenge" editor rows="6" />
    <x-admin.field type="textarea" name="solution" label="Solution" :value="$item->solution" editor rows="6" />
    <x-admin.field type="textarea" name="results" label="Results" :value="$item->results" editor rows="6" />
</div>
@endsection
