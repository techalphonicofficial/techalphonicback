@extends('admin.layouts.app')

@section('title', 'Programmatic SEO pages')

@section('content')
@php
    $byType = $counts->groupBy('page_type');
@endphp
<div class="mb-5 grid gap-3 sm:grid-cols-3">
    @foreach(\App\Enums\LandingPageType::cases() as $t)
        @php $rows = $byType[$t->value] ?? collect(); @endphp
        <a href="{{ route('admin.landing-pages.index', ['page_type' => $t->value]) }}" class="a-card a-card-b block hover:border-brand/40">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t->label() }}</p>
            <p class="mt-1 text-2xl font-bold text-ink">{{ $rows->sum('c') }}</p>
            <p class="text-xs text-slate-500">{{ $rows->firstWhere('status', 'published')?->c ?? 0 }} published · {{ $rows->firstWhere('status', 'draft')?->c ?? 0 }} draft</p>
        </a>
    @endforeach
</div>

<div class="mb-4 flex flex-wrap items-center gap-2">
    <form method="GET" class="flex flex-1 flex-wrap items-center gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search title or slug…" class="a-input max-w-xs">
        <select name="status" class="a-input w-auto" onchange="this.form.submit()"><option value="">All statuses</option>@foreach(\App\Enums\ContentStatus::options() as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
        @foreach($filters as $key => $f)
            <select name="{{ $key }}" class="a-input w-auto" onchange="this.form.submit()"><option value="">{{ $f['label'] }}: all</option>@foreach($f['options'] as $k => $v)<option value="{{ $k }}" @selected((string) request($key) === (string) $k)>{{ $v }}</option>@endforeach</select>
        @endforeach
    </form>
    @can('landing_pages.create')<a href="{{ route('admin.landing-pages.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /> New page</a>@endcan
    @can('seo.bulk')
        <a href="{{ route('admin.landing-pages.bulk') }}" class="a-btn a-btn-dark"><x-icon name="zap" class="size-4" /> Bulk generate</a>
        <a href="{{ route('admin.landing-pages.bulk-seo', request()->query()) }}" class="a-btn a-btn-light">Bulk SEO editor</a>
    @endcan
</div>

<form method="POST" action="{{ route('admin.landing-pages.bulk-action') }}" x-data="bulkSelect" class="a-card overflow-hidden">
    @csrf
    <div class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-2.5" x-show="ids.length" x-cloak>
        <span class="text-sm font-semibold"><span x-text="ids.length"></span> selected</span>
        <select name="bulk_action" class="a-input w-auto">
            <option value="publish">Publish</option><option value="unpublish">Unpublish</option>
            <option value="index">Allow indexing</option><option value="noindex">Set noindex</option><option value="delete">Move to trash</option>
            @can('ai.generate')<option value="ai_write">Write with AI (drafts only)</option>@endcan
        </select>
        <button class="a-btn a-btn-dark a-btn-sm" onclick="return confirm('Apply to selected pages?')">Apply</button>
    </div>
    <div class="overflow-x-auto">
        <table class="a-table">
            <thead><tr>
                <th class="w-8"><input type="checkbox" class="a-check" @change="toggleAll($event)"></th>
                <th>Page</th><th>Type</th><th>Service</th><th>Location / Industry</th><th>SEO</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            @forelse($items as $p)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $p->id }}" data-row-id x-model="ids" class="a-check"></td>
                    <td><a href="{{ route('admin.landing-pages.edit', $p->id) }}" class="font-semibold text-ink hover:text-brand">{{ $p->title }}</a><span class="block text-xs text-slate-400">/{{ $p->slug }}/</span></td>
                    <td><span class="a-badge bg-slate-100 text-slate-600">{{ $p->page_type->label() }}</span></td>
                    <td class="text-slate-600">{{ $p->service?->name }}</td>
                    <td class="text-slate-600">{{ collect([$p->location?->name, $p->industry?->name])->filter()->implode(' · ') }}</td>
                    <td>
                        <span class="flex gap-1 text-[0.65rem] font-bold">
                            <span class="a-badge {{ $p->seo?->meta_title ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">T</span>
                            <span class="a-badge {{ $p->seo?->meta_description ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">D</span>
                            @if($p->seo && ! $p->seo->robots_index)<span class="a-badge bg-slate-200 text-slate-700">NI</span>@endif
                            @if($g = $p->latestAiGeneration)<span class="a-badge {{ $g->statusClasses() }}" title="AI: {{ $g->statusLabel() }}{{ $g->error ? ' · '.$g->error : '' }}"><x-icon name="sparkles" class="size-3" />@if($g->similarity !== null && $g->similarity >= 0.35)!@endif</span>@endif
                        </span>
                    </td>
                    <td><x-admin.status-badge :status="$p->status" /></td>
                    <td class="whitespace-nowrap text-right">
                        @if($p->isPublished())<a href="{{ $p->url }}" target="_blank" class="a-btn a-btn-light a-btn-sm"><x-icon name="arrow-up-right" class="size-3.5" /></a>@endif
                        <a href="{{ route('admin.landing-pages.edit', $p->id) }}" class="a-btn a-btn-light a-btn-sm">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-12 text-center text-slate-500">No programmatic pages yet. <a href="{{ route('admin.landing-pages.create') }}" class="font-semibold text-brand">Create the first one</a>.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</form>
<div class="mt-4">{{ $items->links('admin.partials.pagination') }}</div>
@endsection
