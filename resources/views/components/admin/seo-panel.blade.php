@props(['item', 'defaultTitle' => '', 'url' => null, 'variables' => []])
@php
    $seo = $item->seo;
    $v = fn ($k, $d = null) => old('seo.'.$k, $seo?->{$k} ?? $d);
    $schemaTypes = \App\Models\SchemaTemplate::TYPES;
    $wrapVar = fn (string $name) => '{'.'{'.$name.'}'.'}';
@endphp
@cannot('seo.edit')
    <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">You don't have permission to edit SEO fields. Changes here will not be saved.</p>
@endcannot

<div class="grid gap-6" x-data="{ t: @js((string) $v('meta_title')), d: @js((string) $v('meta_description')) }">
    {{-- Google preview --}}
    <div class="rounded-xl border border-line bg-white p-4">
        <p class="mb-2 text-[0.7rem] font-semibold uppercase tracking-wide text-slate-500">Search preview</p>
        <p class="truncate text-xs text-emerald-700">{{ $url ?? url('/') }}</p>
        <p class="truncate text-lg text-[#1a0dab]" x-text="t || @js($defaultTitle)"></p>
        <p class="line-clamp-2 text-sm text-slate-600" x-text="d || 'Meta description will be generated from the page content if left empty.'"></p>
    </div>

    @if($variables)
        <p class="rounded-lg bg-teal-50 px-3 py-2 text-xs text-teal-800">Variables available: @foreach($variables as $var)<code class="mx-0.5 rounded bg-white px-1">{{ $wrapVar($var) }}</code>@endforeach</p>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="a-label" for="seo_meta_title">Meta title</label>
            <input id="seo_meta_title" name="seo[meta_title]" class="a-input" x-model="t" maxlength="255" placeholder="{{ $defaultTitle }}">
            <p class="a-help flex justify-between"><span>Leave empty to use the page title + site name.</span>
                <span :class="t.length === 0 ? 'text-slate-400' : (t.length < 30 || t.length > 60 ? 'text-amber-600' : 'text-emerald-600')"><span x-text="t.length"></span> / 50–60</span></p>
        </div>
        <div class="md:col-span-2">
            <label class="a-label" for="seo_meta_description">Meta description</label>
            <textarea id="seo_meta_description" name="seo[meta_description]" rows="3" class="a-input" x-model="d" maxlength="500"></textarea>
            <p class="a-help flex justify-between"><span>Leave empty to auto-generate from the introduction.</span>
                <span :class="d.length === 0 ? 'text-slate-400' : (d.length < 120 || d.length > 160 ? 'text-amber-600' : 'text-emerald-600')"><span x-text="d.length"></span> / 140–160</span></p>
        </div>
        <x-admin.field name="seo[focus_keyword]" label="Focus keyword" :value="$v('focus_keyword')" />
        <x-admin.field name="seo[secondary_keywords]" label="Secondary keywords" :value="$v('secondary_keywords')" help="Comma separated" />
        <x-admin.field name="seo[meta_keywords]" label="Meta keywords" :value="$v('meta_keywords')" help="Optional — ignored by Google." />
        <x-admin.field name="seo[canonical_url]" label="Canonical URL" :value="$v('canonical_url')" help="Leave empty for self-canonical (recommended)." placeholder="{{ $url }}" />
    </div>

    <div class="grid gap-3 rounded-xl border border-line p-4 sm:grid-cols-2">
        <x-admin.field type="checkbox" name="seo[robots_index]" label="Allow indexing (index)" :value="$v('robots_index', true)" />
        <x-admin.field type="checkbox" name="seo[robots_follow]" label="Follow links (follow)" :value="$v('robots_follow', true)" />
        <x-admin.field type="checkbox" name="seo[include_in_sitemap]" label="Include in XML sitemap" :value="$v('include_in_sitemap', true)" />
        <x-admin.field type="checkbox" name="seo[schema_enabled]" label="Output structured data (schema)" :value="$v('schema_enabled', true)" />
        <x-admin.field type="select" name="seo[sitemap_changefreq]" label="Sitemap change frequency" :value="$v('sitemap_changefreq')" :options="array_combine($f = ['daily', 'weekly', 'monthly', 'yearly'], array_map('ucfirst', $f))" empty="Default" />
        <x-admin.field type="number" name="seo[sitemap_priority]" label="Sitemap priority (0–1)" :value="$v('sitemap_priority')" step="0.1" min="0" max="1" />
    </div>

    <details class="rounded-xl border border-line p-4" @if($seo?->og_title || $seo?->og_image_id) open @endif>
        <summary class="cursor-pointer text-sm font-semibold text-ink">Social sharing (Open Graph & Twitter)</summary>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <x-admin.field name="seo[og_title]" label="OG title" :value="$v('og_title')" />
            <x-admin.field name="seo[twitter_title]" label="Twitter title" :value="$v('twitter_title')" />
            <x-admin.field type="textarea" rows="2" name="seo[og_description]" label="OG description" :value="$v('og_description')" />
            <x-admin.field type="textarea" rows="2" name="seo[twitter_description]" label="Twitter description" :value="$v('twitter_description')" />
            <x-admin.media-picker name="seo[og_image_id]" label="OG image (1200×630)" :media="$seo?->ogImage" />
            <x-admin.media-picker name="seo[twitter_image_id]" label="Twitter image" :media="$seo?->twitterImage" />
        </div>
    </details>

    @can('schema.edit')
        <details class="rounded-xl border border-line p-4" @if($seo?->schema_json) open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-ink">Custom schema (JSON-LD)</summary>
            <div class="mt-4 grid gap-4">
                <p class="text-xs text-slate-500">Organization, WebSite, WebPage, BreadcrumbList, the page-type schema (Service / BlogPosting…) and FAQPage are generated automatically. Add extra nodes here — e.g. Review, Product, Event. Same <code>@id</code> overrides the generated node. Variables like <code>@{{page_url}}</code> work.</p>
                <x-admin.field type="select" name="seo[schema_type]" label="Primary schema type (informational)" :value="$v('schema_type')" :options="array_combine($schemaTypes, $schemaTypes)" empty="Automatic" />
                <x-admin.field type="textarea" rows="10" name="seo[schema_json]" label="JSON-LD" :value="$v('schema_json')" class="font-mono" placeholder='{ "@type": "Review", ... }' />
            </div>
        </details>
    @endcan
</div>
