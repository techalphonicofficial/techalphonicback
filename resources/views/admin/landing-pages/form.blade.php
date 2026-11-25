@extends('admin.content.form', [
    'tabs' => ['basic' => 'Basic information', 'content' => 'Unique content', 'sections' => 'Sections', 'blocks' => 'Page builder', 'seo' => 'SEO', 'faqs' => 'FAQs', 'links' => 'Internal links'],
    'seoVariables' => $variables,
])

@php $wrap = fn ($v) => '{'.'{'.$v.'}'.'}'; @endphp

@section('aside')<x-admin.ai-panel :page="$item" />@endsection

@section('outside-form')@if($item->exists)<form id="ai-generate" method="POST" action="{{ route('admin.landing-pages.ai', $item->getKey()) }}" class="hidden">@csrf</form>@endif @endsection

@section('tab-basic')
<div class="mb-5 rounded-xl border border-teal/30 bg-teal-50 p-4 text-sm text-teal-900">
    <p class="font-semibold">Avoid thin “doorway” pages</p>
    <p class="mt-1">Give every page something unique: local facts, an industry-specific introduction, its own FAQs and real examples. Pages start as <strong>Draft + noindex</strong>; switch indexing on in the SEO tab once the content is genuinely useful.</p>
</div>
<div class="grid gap-4 md:grid-cols-2"
     x-data="{ pageType: @js(old('page_type', $item->page_type?->value ?? $item->page_type ?? 'service_location')) }">
    <x-admin.field type="select" name="page_type" label="Page type" :value="$item->page_type?->value ?? $item->page_type" :options="\App\Enums\LandingPageType::options()" :empty="false" x-model="pageType" />
    <x-admin.field type="select" name="service_id" label="Service" :value="$item->service_id" :options="$services" required />
    <div x-show="pageType !== 'service_industry'" x-cloak>
        <x-admin.field type="select" name="location_id" label="Location" :value="$item->location_id" :options="$locations->mapWithKeys(fn ($l) => [$l->id => $l->display_name.($l->state && ! $l->isArea() ? ', '.$l->state : '')])" help="Required for location page types." ::disabled="pageType === 'service_industry'" ::required="pageType !== 'service_industry'" />
    </div>
    <div x-show="pageType !== 'service_location'" x-cloak>
        <x-admin.field type="select" name="industry_id" label="Industry" :value="$item->industry_id" :options="$industries" help="Required for industry page types." ::disabled="pageType === 'service_location'" ::required="pageType !== 'service_location'" />
    </div>
    <x-admin.field name="title" label="Internal title / link anchor" :value="$item->title" required class="md:col-span-2" />
    <div class="md:col-span-2" x-data="slugField(@js(old('slug', $item->slug)), 'title', true)">
        <label class="a-label" for="slug">URL slug</label>
        <div class="flex items-center rounded-lg border border-line bg-mist text-sm"><span class="px-3 text-slate-500">{{ url('/') }}/</span><input id="slug" name="slug" x-model="slug" @change="normalise()" class="a-input rounded-l-none border-0"><span class="px-2 text-slate-500">/</span></div>
        @error('slug')<p class="a-error">{{ $message }}</p>@enderror
    </div>
    <x-admin.field name="h1" label="H1" :value="$item->h1" help="Variables allowed, e.g. {{ $wrap('service_name') }} in {{ $wrap('location_name') }}" />
    <x-admin.field name="hero_subheading" label="Hero subheading" :value="$item->hero_subheading" />
    <x-admin.field type="select" name="hero_layout" label="Hero layout" :value="$item->hero_layout" :options="\App\Models\Page::HERO_LAYOUTS" :empty="false" help="Vary layouts so programmatic pages don't all look identical." />
    <x-admin.media-picker name="featured_image_id" label="Hero / featured image" :media="$item->featuredImage" />
    <div class="grid gap-2 md:col-span-2 sm:grid-cols-3">
        <x-admin.field type="checkbox" name="faq_enabled" label="Show FAQs" :value="$item->faq_enabled ?? true" />
        <x-admin.field type="checkbox" name="show_testimonials" label="Show testimonials" :value="$item->show_testimonials ?? true" />
        <x-admin.field type="checkbox" name="show_industries" label="Show industries grid" :value="$item->show_industries ?? true" />
    </div>
    <x-admin.field name="faq_title" label="FAQ section title" :value="$item->faq_title" class="md:col-span-2" />
</div>
<input type="hidden" name="generated_by" value="{{ $item->generated_by ?? 'manual' }}">
@endsection

@section('tab-content')
<div class="grid gap-4">
    <x-admin.field type="textarea" name="introduction" label="Introduction (shown in the hero)" :value="$item->introduction" rows="5" help="Make this specific to the location/industry — mention real local context." />
    <x-admin.field type="textarea" name="content" label="Main content" :value="$item->content" editor rows="14" />
    <div class="grid gap-4 md:grid-cols-2">
        <x-admin.field name="cta[heading]" label="CTA heading" :value="$item->cta['heading'] ?? ''" />
        <x-admin.field name="cta[text]" label="CTA text" :value="$item->cta['text'] ?? ''" />
        <x-admin.field name="cta[button_text]" label="CTA button text" :value="$item->cta['button_text'] ?? ''" />
        <x-admin.field name="cta[button_url]" label="CTA button URL" :value="$item->cta['button_url'] ?? ''" />
    </div>
</div>
@endsection

@section('tab-sections')
<div class="grid gap-6">
    <x-admin.repeater name="sections" label="Custom content sections" :rows="$item->sections ?? []" :fields="['heading' => 'Heading (H2)', 'content:textarea' => 'Content (HTML)']" help="Unique sections for this page, e.g. 'SEO challenges for businesses in Noida'." />
    <x-admin.repeater name="benefits" label="Benefits (empty = inherit from service)" :rows="$item->benefits ?? []" :fields="['title' => 'Benefit', 'text' => 'Detail', 'icon:icon' => 'Icon']" />
    <x-admin.repeater name="process" label="Process (empty = inherit from service)" :rows="$item->process ?? []" :fields="['title' => 'Step', 'text:textarea' => 'Description']" />
    <x-admin.repeater name="why_choose_us" label="Why choose us" :rows="$item->why_choose_us ?? []" :fields="['title' => 'Title', 'icon:icon' => 'Icon', 'text:textarea' => 'Text']" />
</div>
@endsection
