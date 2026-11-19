@extends('admin.content.form', ['tabs' => [
    'basic' => 'Basic information', 'content' => 'Content', 'sections' => 'Sections', 'blocks' => 'Page builder',
    'programmatic' => 'Programmatic template', 'seo' => 'SEO', 'faqs' => 'FAQs', 'links' => 'Internal links',
]])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="name" label="Service name" :value="$item->name" required />
    <x-admin.field name="menu_label" label="Menu label" :value="$item->menu_label" />
    <div class="md:col-span-2" x-data="slugField(@js(old('slug', $item->slug)), 'name')">
        <label class="a-label" for="slug">URL slug</label>
        <div class="flex items-center rounded-lg border border-line bg-mist text-sm"><span class="px-3 text-slate-500">{{ url('/') }}/</span><input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input rounded-l-none border-0"><span class="px-2 text-slate-500">/</span></div>
        <p class="a-help">Keep established URLs from the old site (e.g. <code>laravel-development</code>).</p>
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    <x-admin.field type="select" name="service_category_id" label="Category" :value="$item->service_category_id" :options="$categories" />
    <x-admin.field type="select" name="parent_id" label="Parent service" :value="$item->parent_id" :options="$parents" help="e.g. Ecommerce SEO → parent: SEO" />
    <x-admin.field type="select" name="icon" label="Icon" :value="$item->icon" :options="array_combine(\App\Support\Icons::names(), \App\Support\Icons::names())" />
    <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
    <x-admin.field type="textarea" name="short_description" label="Short description (cards & menus)" :value="$item->short_description" rows="2" :counter="[80, 160]" class="md:col-span-2" />
    <x-admin.field type="checkbox" name="is_featured" label="Featured on homepage" :value="$item->is_featured" />
</div>
@endsection

@section('tab-content')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="hero_heading" label="Hero H1" :value="$item->hero_heading" help="Defaults to service name." class="md:col-span-2" />
    <x-admin.field type="textarea" name="hero_description" label="Hero description" :value="$item->hero_description" rows="3" class="md:col-span-2" />
    <x-admin.field type="select" name="hero_layout" label="Hero layout" :value="$item->hero_layout" :options="\App\Models\Page::HERO_LAYOUTS" :empty="false" />
    <div></div>
    <x-admin.media-picker name="hero_image_id" label="Hero image" :media="$item->heroImage" />
    <x-admin.media-picker name="featured_image_id" label="Featured image (cards / social)" :media="$item->featuredImage" />
    <x-admin.field type="textarea" name="description" label="Overview (lead paragraph)" :value="$item->description" rows="3" class="md:col-span-2" />
    <div class="md:col-span-2"><x-admin.field type="textarea" name="content" label="Main content" :value="$item->content" editor rows="14" /></div>
    <x-admin.field name="cta_heading" label="CTA heading" :value="$item->cta_heading" />
    <x-admin.field name="cta_description" label="CTA text" :value="$item->cta_description" />
</div>
@endsection

@section('tab-sections')
<p class="mb-4 text-sm text-slate-600">These power the default service layout (used when the Page builder is empty). Leave any list empty to hide that section.</p>
<div class="grid gap-6">
    <x-admin.repeater name="hero_highlights" label="Hero: What you get (top right card)" :rows="$item->hero_highlights ?? []" :fields="['title' => 'Title', 'icon:icon' => 'Icon', 'text:textarea' => 'Description']" help="Separate deliverables shown inside the top 'What you get' hero card. If left empty, benefits or first features are used." />
    <x-admin.repeater name="features" label="What's included (feature cards section below)" :rows="$item->features ?? []" :fields="['title' => 'Title', 'icon:icon' => 'Icon', 'text:textarea' => 'Description', 'url' => 'Link (optional)']" help="Full grid of services/features displayed in the 'Our [Service] services' section." />
    <x-admin.repeater name="benefits" label="Benefits" :rows="$item->benefits ?? []" :fields="['title' => 'Benefit', 'text' => 'Detail', 'icon:icon' => 'Icon']" />
    <x-admin.repeater name="process" label="Process steps" :rows="$item->process ?? []" :fields="['title' => 'Step', 'text:textarea' => 'Description']" />
    <x-admin.repeater name="technologies" label="Technologies" :rows="$item->technologies ?? []" :fields="['title' => 'Technology']" />
    <x-admin.repeater name="pricing" label="Pricing plans" :rows="$item->pricing ?? []" :fields="['title' => 'Plan name', 'value' => 'Price', 'suffix' => 'Suffix (/month)', 'text' => 'Short description', 'features:textarea' => 'Features (one per line)', 'cta_text' => 'Button text', 'url' => 'Button URL', 'featured:checkbox' => 'Most popular']" />
</div>
@endsection

@section('tab-programmatic')
@php $lt = $item->landing_template ?? []; @endphp
<p class="mb-4 text-sm text-slate-600">Default copy for programmatic pages generated from this service (Service + Location / Industry). Use variables such as
    @foreach(\App\Services\TemplateRenderer::VARIABLES as $var)<code class="mx-0.5 rounded bg-mist px-1 text-xs">{{ '{'.'{'.$var.'}'.'}' }}</code>@endforeach.
    Every generated page can still be fully edited — write natural, useful copy, not keyword lists.</p>
<div class="grid gap-4">
    <x-admin.field name="landing_template[slug_base]" label="Slug base" :value="$lt['slug_base'] ?? ''" help="e.g. 'seo' → /seo-company-in-noida/. Defaults to this service's slug." />
    @foreach(['service_location' => 'Service + Location', 'service_industry' => 'Service + Industry', 'service_location_industry' => 'Service + Location + Industry'] as $type => $label)
        <details class="rounded-xl border border-line p-4">
            <summary class="cursor-pointer text-sm font-semibold text-ink">{{ $label }}</summary>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach(['slug' => 'URL pattern', 'title' => 'Title', 'h1' => 'H1', 'hero_subheading' => 'Hero subheading', 'meta_title' => 'Meta title', 'focus_keyword' => 'Focus keyword'] as $k => $l)
                    <x-admin.field :name="'landing_template['.$type.']['.$k.']'" :label="$l" :value="$lt[$type][$k] ?? ''" :placeholder="$templateDefaults[$type][$k] ?? ''" />
                @endforeach
                <x-admin.field type="textarea" :name="'landing_template['.$type.'][meta_description]'" label="Meta description" :value="$lt[$type]['meta_description'] ?? ''" :placeholder="$templateDefaults[$type]['meta_description']" rows="2" class="md:col-span-2" />
                <x-admin.field type="textarea" :name="'landing_template['.$type.'][introduction]'" label="Introduction (HTML)" :value="$lt[$type]['introduction'] ?? ''" :placeholder="$templateDefaults[$type]['introduction']" rows="4" class="md:col-span-2" />
            </div>
        </details>
    @endforeach
    <x-admin.repeater name="landing_template[why_choose_us]" label="Why choose us (shared by generated pages)" :rows="$lt['why_choose_us'] ?? []" :fields="['title' => 'Title', 'icon:icon' => 'Icon', 'text:textarea' => 'Text']" />
</div>
@endsection
