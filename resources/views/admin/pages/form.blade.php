@extends('admin.content.form', ['tabs' => ['basic' => 'Basic information', 'content' => 'Content', 'blocks' => 'Page builder', 'seo' => 'SEO', 'faqs' => 'FAQs', 'links' => 'Internal links']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="title" label="Page title" :value="$item->title" required class="md:col-span-2" />
    <div class="md:col-span-2" x-data="slugField(@js(old('slug', $item->slug)), 'title')">
        <label class="a-label" for="slug">URL slug</label>
        <div class="flex items-center rounded-lg border border-line bg-mist text-sm"><span class="px-3 text-slate-500">{{ url('/') }}/</span><input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input rounded-l-none border-0"><span class="px-2 text-slate-500">/</span></div>
        <p class="a-help">Lowercase, hyphens only. Changing it on a live page creates a 301 redirect automatically.</p>
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    <x-admin.field name="h1" label="H1 heading" :value="$item->h1" help="Defaults to the page title." />
    <x-admin.field name="hero_subheading" label="Hero subheading" :value="$item->hero_subheading" />
    <x-admin.field type="select" name="template" label="Template" :value="$item->template" :options="\App\Models\Page::TEMPLATES" :empty="false" />
    <x-admin.field type="select" name="hero_layout" label="Hero layout" :value="$item->hero_layout" :options="\App\Models\Page::HERO_LAYOUTS" :empty="false" />
    <x-admin.field type="select" name="parent_id" label="Parent page" :value="$item->parent_id" :options="$parents" help="Used for breadcrumbs and hub pages." />
    <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
    <x-admin.field type="textarea" name="excerpt" label="Intro / excerpt" :value="$item->excerpt" rows="3" class="md:col-span-2" />
    <div class="md:col-span-2"><x-admin.media-picker name="featured_image_id" label="Featured / hero image" :media="$item->featuredImage" /></div>
    <x-admin.field type="checkbox" name="show_in_search" label="Show in site search" :value="$item->show_in_search ?? true" />
</div>
@endsection

@section('tab-content')
<x-admin.field type="textarea" name="content" label="Main content" :value="$item->content" editor rows="14" help="Use H2/H3 headings for structure. For rich layouts use the Page builder tab." />
@endsection
