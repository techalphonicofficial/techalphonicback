{{--
    Shared edit screen for CMS content. Child views @extend this with:
      $tabs   e.g. ['basic' => 'Basic information', 'content' => 'Content', 'seo' => 'SEO', 'faqs' => 'FAQs', 'blocks' => 'Page builder', 'links' => 'Internal links']
      sections: tab-basic, tab-content, tab-images (optional), sidebar (optional)
--}}
@extends('admin.layouts.app')

@php
    $isNew = ! $item->exists;
    $tabs ??= ['basic' => 'Basic information', 'seo' => 'SEO'];
    $titleField = $item->getAttribute('title') ?? $item->getAttribute('name') ?? $item->getAttribute('question') ?? $item->getAttribute('old_url');
    $publicUrl = $item->exists && method_exists($item, 'getUrlAttribute') && $item->slug ? $item->url : null;
    $firstErrorTab = collect(['seo' => 'seo.', 'faqs' => 'faq_ids', 'blocks' => 'blocks_json', 'links' => 'links'])
        ->first(fn ($prefix) => collect($errors->keys())->contains(fn ($k) => str_starts_with($k, $prefix)));
    $initialTab = array_search($firstErrorTab, ['seo' => 'seo.', 'faqs' => 'faq_ids', 'blocks' => 'blocks_json', 'links' => 'links']) ?: array_key_first($tabs);
@endphp

@section('title', $isNew ? 'New '.strtolower($ctrl['label']) : 'Edit: '.\Illuminate\Support\Str::limit($titleField, 50))

@section('content')
<form method="POST" action="{{ $isNew ? route($ctrl['route'].'.store') : route($ctrl['route'].'.update', $item->getKey()) }}" data-guard
      x-data="{ tab: @js($initialTab) }" class="grid gap-6 xl:grid-cols-[1fr_320px]">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <div class="min-w-0">
        <div class="mb-4 flex flex-wrap items-center gap-2 text-[0.8125rem]">
            <a href="{{ route($ctrl['route'].'.index') }}" class="a-btn a-btn-ghost a-btn-sm -ml-2"><x-icon name="chevron-right" class="size-3.5 rotate-180" /> All {{ strtolower($ctrl['plural']) }}</a>
            @if($publicUrl)
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="a-chip max-w-full hover:border-brand/40 hover:text-brand">
                    <span class="a-dot text-emerald-500"></span><span class="truncate font-mono text-[0.6875rem]">{{ parse_url($publicUrl, PHP_URL_PATH) }}</span><x-icon name="arrow-up-right" class="size-3 shrink-0" />
                </a>
            @endif
        </div>

        <div class="a-card">
            <nav class="flex gap-1 overflow-x-auto border-b border-line px-3" role="tablist">
                @foreach($tabs as $key => $label)
                    <button type="button" role="tab" class="a-tab" :aria-selected="(tab === '{{ $key }}').toString()" :class="tab === '{{ $key }}' ? 'border-brand text-ink' : 'border-transparent text-slate-500 hover:border-line-strong hover:text-ink'" @click="tab = '{{ $key }}'">{{ $label }}</button>
                @endforeach
            </nav>
            <div class="a-card-b">
                @foreach($tabs as $key => $label)
                    <section x-show="tab === '{{ $key }}'" @if($key !== $initialTab) x-cloak @endif>
                        @switch($key)
                            @case('seo')
                                <x-admin.seo-panel :item="$item" :default-title="$titleField" :url="$publicUrl" :variables="$seoVariables ?? []" />
                                @break
                            @case('faqs')
                                <x-admin.faq-picker :item="$item" :all-faqs="$allFaqs" />
                                @break
                            @case('blocks')
                                <x-admin.block-builder :item="$item" />
                                @break
                            @case('links')
                                <x-admin.links-editor :item="$item" />
                                @break
                            @default
                                @yield('tab-'.$key)
                        @endswitch
                    </section>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Sidebar: publishing + audit --}}
    <aside class="grid content-start gap-4 xl:sticky xl:top-20 xl:self-start">
        <div class="a-card">
            <div class="a-card-h"><p class="a-card-t">Publishing</p>@if($item->exists && isset($item->status))<x-admin.status-badge :status="$item->status" />@endif</div>
            <div class="a-card-b grid gap-3">
                @if(array_key_exists('status', $item->getAttributes()) || $isNew && $ctrl['publishable'])
                    @if($ctrl['publishable'])
                        <x-admin.field type="select" name="status" label="Status" :value="$item->status?->value ?? $item->status" :options="\App\Enums\ContentStatus::options()" :empty="false" />
                        <x-admin.field type="datetime-local" name="published_at" label="Publish date" :value="$item->published_at?->format('Y-m-d\TH:i')" help="A future date schedules the page." />
                    @else
                        <x-admin.field type="select" name="status" label="Status" :value="$item->status" :options="['published' => 'Active', 'draft' => 'Inactive']" :empty="false" />
                    @endif
                @endif
                @yield('sidebar')

                <div class="grid gap-2 pt-2">
                    <button type="submit" class="a-btn a-btn-primary w-full"><x-icon name="check" class="size-4" /> {{ $isNew ? 'Create' : 'Save changes' }}</button>
                    @if(! $isNew && $ctrl['previewable'])
                        <button type="submit" name="action" value="preview" formtarget="_blank" class="a-btn a-btn-light w-full"><x-icon name="search" class="size-4" /> Save & preview</button>
                    @endif
                </div>
            </div>
        </div>

        @yield('aside')

        @if(! $isNew)
            <div class="a-card">
                <div class="a-card-b grid gap-2">
                    @if($ctrl['publishable'] && auth()->user()->can($ctrl['resource'].'.publish'))
                        @if($item->isPublished())
                            <button form="act-unpublish" class="a-btn a-btn-light w-full">Unpublish</button>
                        @else
                            <button form="act-publish" class="a-btn a-btn-dark w-full">Publish now</button>
                        @endif
                    @endif
                    @if($ctrl['publishable'] && ! isset($item->page_type))
                        <button form="act-duplicate" class="a-btn a-btn-light w-full">Duplicate</button>
                    @endif
                    @if(method_exists($item, 'trashed') && $item->trashed())
                        <button form="act-restore" class="a-btn a-btn-light w-full">Restore from trash</button>
                    @else
                        <button form="act-delete" class="a-btn a-btn-danger w-full" onclick="return confirm('Delete this item?')"><x-icon name="trash" class="size-3.5" /> Delete</button>
                    @endif
                </div>
            </div>
        @endif

        @if($audit)
            <div class="a-card" x-data="{ o: false }">
                <button type="button" class="a-card-h w-full" @click="o = ! o">
                    <span class="a-card-t">SEO checklist</span>
                    <span class="a-badge {{ $audit['score'] >= 80 ? 'bg-emerald-50 text-emerald-700' : ($audit['score'] >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">{{ $audit['score'] }}/100</span>
                </button>
                <ul x-show="o" x-collapse class="grid gap-1.5 p-4 text-xs">
                    @foreach($audit['checks'] as $c)
                        <li class="flex gap-2" title="{{ $c['hint'] }}">
                            <span class="{{ $c['pass'] ? 'text-emerald-600' : 'text-red-500' }}">{{ $c['pass'] ? '✓' : '✗' }}</span>
                            <span class="{{ $c['pass'] ? 'text-slate-600' : 'text-ink' }}">{{ $c['label'] }}@unless($c['pass'])<span class="block text-slate-400">{{ $c['hint'] }}</span>@endunless</span>
                        </li>
                    @endforeach
                    <li class="mt-2 text-[0.65rem] text-slate-400">Internal guidance only — not a reproduction of Google's ranking algorithm.</li>
                </ul>
            </div>
        @endif
    </aside>
</form>

@unless($isNew)
    <form id="act-publish" method="POST" action="{{ route($ctrl['route'].'.publish', $item->getKey()) }}" class="hidden">@csrf</form>
    <form id="act-unpublish" method="POST" action="{{ route($ctrl['route'].'.unpublish', $item->getKey()) }}" class="hidden">@csrf</form>
    <form id="act-duplicate" method="POST" action="{{ route($ctrl['route'].'.duplicate', $item->getKey()) }}" class="hidden">@csrf</form>
    <form id="act-restore" method="POST" action="{{ route($ctrl['route'].'.restore', $item->getKey()) }}" class="hidden">@csrf</form>
    <form id="act-delete" method="POST" action="{{ route($ctrl['route'].'.destroy', $item->getKey()) }}" class="hidden">@csrf @method('DELETE')</form>
    @yield('outside-form')
@endunless
@endsection
