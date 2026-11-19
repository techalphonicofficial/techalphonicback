@extends('admin.content.form', ['tabs' => ['basic' => 'Basic information', 'content' => 'Content', 'blocks' => 'Page builder', 'seo' => 'SEO', 'faqs' => 'FAQs']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="name" label="Name" :value="$item->name" required />
    <x-admin.field name="menu_label" label="Menu label" :value="$item->menu_label" help="Shorter name for menus." />
    <div class="md:col-span-2" x-data="slugField(@js(old('slug', $item->slug)), 'name')">
        <label class="a-label" for="slug">URL slug</label>
        <div class="flex items-center rounded-lg border border-line bg-mist text-sm"><span class="px-3 text-slate-500">{{ url('/') }}/</span><input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input rounded-l-none border-0"><span class="px-2 text-slate-500">/</span></div>
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    <x-admin.field name="h1" label="H1 heading" :value="$item->h1" />
    <x-admin.field type="select" name="icon" label="Icon" :value="$item->icon" :options="array_combine(\App\Support\Icons::names(), \App\Support\Icons::names())" />
    <x-admin.field type="textarea" name="short_description" label="Short description" :value="$item->short_description" rows="3" class="md:col-span-2" />
    <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
    <x-admin.media-picker name="image_id" label="Hero image" :media="$item->image" />
</div>
@endsection

@section('tab-content')
<x-admin.field type="textarea" name="description" label="Long description" :value="$item->description" editor rows="12" />
@endsection
