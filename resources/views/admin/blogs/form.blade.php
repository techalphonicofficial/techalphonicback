@extends('admin.content.form', ['tabs' => ['basic' => 'Post', 'content' => 'Content', 'seo' => 'SEO', 'faqs' => 'FAQs', 'links' => 'Internal links']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="title" label="Title" :value="$item->title" required class="md:col-span-2" :counter="[40, 70]" />
    <div class="md:col-span-2" x-data="slugField(@js(old('slug', $item->slug)), 'title')">
        <label class="a-label" for="slug">URL slug</label>
        <div class="flex items-center rounded-lg border border-line bg-mist text-sm"><span class="px-3 text-slate-500">{{ url('/blog') }}/</span><input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input rounded-l-none border-0"><span class="px-2 text-slate-500">/</span></div>
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    <x-admin.field type="select" name="blog_category_id" label="Category" :value="$item->blog_category_id" :options="$categories" />
    <x-admin.field type="select" name="author_id" label="Author" :value="$item->author_id" :options="$authors" />
    <x-admin.field name="tags" label="Tags" :value="$item->exists ? $item->tags->pluck('name')->implode(', ') : ''" help="Comma separated" class="md:col-span-2" />
    <x-admin.field type="multiselect" name="service_ids" label="Related services" :value="$item->exists ? $item->services->pluck('id')->all() : []" :options="$services" class="md:col-span-2" help="Ctrl/Cmd-click to select several. Shown in the sidebar and used for internal linking." />
    <x-admin.field type="textarea" name="excerpt" label="Excerpt" :value="$item->excerpt" rows="3" class="md:col-span-2" :counter="[120, 160]" />
    <div class="md:col-span-2"><x-admin.media-picker name="featured_image_id" label="Featured image" :media="$item->featuredImage" help="1600×800 recommended. Alt text is required for SEO." /></div>
    <x-admin.field type="checkbox" name="show_toc" label="Show table of contents" :value="$item->show_toc ?? true" />
    <x-admin.field type="checkbox" name="faq_enabled" label="Show FAQs" :value="$item->faq_enabled ?? true" />
    <x-admin.field type="checkbox" name="is_featured" label="Featured post" :value="$item->is_featured" />
</div>
@endsection

@section('tab-content')
<x-admin.field type="textarea" name="content" label="Article" :value="$item->content" editor rows="20" help="Use H2 and H3 headings — the table of contents is generated from them automatically." />
@endsection
