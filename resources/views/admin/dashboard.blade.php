@extends('admin.layouts.app')

@section('title', 'Dashboard')

@php
    $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    // Leads: last 14 days as a series, plus the change against the 14 days before.
    $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->toDateString());
    $series = $days->map(fn ($d) => (int) ($leadsByDay[$d] ?? 0));
    $leads14 = $series->sum();
    $trend = $leadsPrev > 0 ? round(($leads14 - $leadsPrev) / $leadsPrev * 100) : ($leads14 > 0 ? 100 : 0);
    $maxDay = max(1, $series->max());

    // Sparkline points for the leads KPI (viewBox 100 × 28).
    $spark = $series->values()->map(fn ($c, $i) => round($i / 13 * 100, 2).','.round(26 - $c / $maxDay * 22, 2))->implode(' ');

    $published = $stats['Published pages'] ?? 0;
    $totalPages = max(1, $stats['Total pages'] ?? 0);
    $publishedPct = round($published / $totalPages * 100);

    // Secondary inventory tiles: label => [icon, route, tone]
    $inventory = [
        'Services' => ['briefcase', 'admin.services.index', 'bg-sky-50 text-sky-600'],
        'Locations' => ['map-pin', 'admin.locations.index', 'bg-emerald-50 text-emerald-600'],
        'Industries' => ['building', 'admin.industries.index', 'bg-violet-50 text-violet-600'],
        'FAQs' => ['message', 'admin.faqs.index', 'bg-amber-50 text-amber-600'],
        'Media' => ['image', 'admin.media.index', 'bg-rose-50 text-rose-600'],
        'Redirects' => ['link', 'admin.redirects.index', 'bg-slate-100 text-slate-600'],
    ];
@endphp

@section('page-head')
<div class="a-page-head">
    <div>
        <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500"><x-icon name="calendar" class="size-3.5" /> {{ now()->format('l, j F Y') }}</p>
        <h1 class="a-page-title mt-1">{{ $greeting }}, {{ $firstName }}</h1>
        <p class="a-page-desc">Here’s what’s happening across {{ setting('site_name') }} today.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @can('blogs.create')<a href="{{ route('admin.blogs.create') }}" class="a-btn a-btn-light"><x-icon name="pen" class="size-4" /> New blog post</a>@endcan
        @can('landing_pages.create')<a href="{{ route('admin.landing-pages.create') }}" class="a-btn a-btn-primary"><x-icon name="rocket" class="size-4" /> New SEO page</a>@endcan
    </div>
</div>
@endsection

@section('content')
{{-- ================= KPIs ================= --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    {{-- Leads --}}
    <a href="{{ route('admin.leads.index') }}" class="a-card group p-5 transition hover:border-line-strong hover:shadow-[var(--shadow-pop)]">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[0.8125rem] font-medium text-slate-500">Leads · last 14 days</p>
                <p class="mt-2 flex items-baseline gap-2">
                    <span class="text-[1.75rem] font-semibold tracking-[-0.03em] text-ink">{{ number_format($leads14) }}</span>
                    <span class="a-badge {{ $trend >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $trend >= 0 ? '▲' : '▼' }} {{ abs($trend) }}%</span>
                </p>
            </div>
            <span class="a-stat-icon bg-brand-50 text-brand"><x-icon name="target" class="size-[18px]" /></span>
        </div>
        <svg viewBox="0 0 100 28" preserveAspectRatio="none" class="mt-3 h-10 w-full" aria-hidden="true">
            <defs><linearGradient id="spark" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#167B8B" stop-opacity=".22"/><stop offset="1" stop-color="#167B8B" stop-opacity="0"/></linearGradient></defs>
            <polygon points="0,28 {{ $spark }} 100,28" fill="url(#spark)" />
            <polyline points="{{ $spark }}" fill="none" stroke="#167B8B" stroke-width="1.5" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" />
        </svg>
        <p class="mt-2 text-xs text-slate-500">{{ $newLeads }} awaiting first contact · {{ number_format($stats['Leads'] ?? 0) }} all time</p>
    </a>

    {{-- Published pages --}}
    <a href="{{ route('admin.pages.index') }}" class="a-card p-5 transition hover:border-line-strong hover:shadow-[var(--shadow-pop)]">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[0.8125rem] font-medium text-slate-500">Published pages</p>
                <p class="mt-2 text-[1.75rem] font-semibold tracking-[-0.03em] text-ink">{{ number_format($published) }}<span class="text-base font-medium text-slate-400"> / {{ number_format($stats['Total pages'] ?? 0) }}</span></p>
            </div>
            <span class="a-stat-icon bg-emerald-50 text-emerald-600"><x-icon name="file-text" class="size-[18px]" /></span>
        </div>
        <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-mist"><div class="h-full rounded-full bg-gradient-to-r from-brand to-teal" style="width: {{ $publishedPct }}%"></div></div>
        <p class="mt-3 flex justify-between text-xs text-slate-500"><span>{{ $publishedPct }}% live</span><span>{{ number_format($stats['Draft pages'] ?? 0) }} drafts</span></p>
    </a>

    {{-- Programmatic SEO --}}
    <a href="{{ route('admin.landing-pages.index') }}" class="a-card p-5 transition hover:border-line-strong hover:shadow-[var(--shadow-pop)]">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[0.8125rem] font-medium text-slate-500">Programmatic SEO pages</p>
                <p class="mt-2 text-[1.75rem] font-semibold tracking-[-0.03em] text-ink">{{ number_format($stats['SEO pages'] ?? 0) }}</p>
            </div>
            <span class="a-stat-icon bg-violet-50 text-violet-600"><x-icon name="rocket" class="size-[18px]" /></span>
        </div>
        <p class="mt-5 text-xs text-slate-500">Across {{ number_format($stats['Services'] ?? 0) }} services, {{ number_format($stats['Locations'] ?? 0) }} locations and {{ number_format($stats['Industries'] ?? 0) }} industries</p>
        @if($seoReport && $seoReport['draft_programmatic'])
            <p class="mt-2 flex items-center gap-1.5 text-xs font-medium text-amber-700"><span class="a-dot"></span>{{ $seoReport['draft_programmatic'] }} drafts awaiting review</p>
        @endif
    </a>

    {{-- Blog --}}
    <a href="{{ route('admin.blogs.index') }}" class="a-card p-5 transition hover:border-line-strong hover:shadow-[var(--shadow-pop)]">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[0.8125rem] font-medium text-slate-500">Blog posts</p>
                <p class="mt-2 text-[1.75rem] font-semibold tracking-[-0.03em] text-ink">{{ number_format($stats['Blogs'] ?? 0) }}</p>
            </div>
            <span class="a-stat-icon bg-amber-50 text-amber-600"><x-icon name="pen" class="size-[18px]" /></span>
        </div>
        <p class="mt-5 text-xs text-slate-500">{{ number_format($stats['FAQs'] ?? 0) }} FAQs and {{ number_format($stats['Media'] ?? 0) }} media files support your content</p>
    </a>
</div>

{{-- Inventory strip --}}
<div class="mt-4 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-line bg-line shadow-[var(--shadow-card)] sm:grid-cols-3 xl:grid-cols-6">
    @foreach($inventory as $label => [$icon, $route, $tone])
        <a href="{{ route($route) }}" class="group flex items-center gap-3 bg-white px-4 py-3.5 transition hover:bg-mist/70">
            <span class="a-stat-icon size-8 {{ $tone }}"><x-icon :name="$icon" class="size-4" /></span>
            <span class="min-w-0">
                <span class="block text-lg font-semibold leading-tight tracking-[-0.02em] text-ink">{{ number_format($stats[$label] ?? 0) }}</span>
                <span class="block text-xs text-slate-500">{{ $label }}</span>
            </span>
            <x-icon name="chevron-right" class="ml-auto size-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-500" />
        </a>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1.45fr_1fr] [&>*]:min-w-0">
    {{-- ================= Leads ================= --}}
    @can('leads.view')
        <div class="a-card">
            <div class="a-card-h">
                <div>
                    <p class="a-card-t">Enquiries</p>
                    <p class="a-card-d">Daily volume over the last 14 days</p>
                </div>
                <a href="{{ route('admin.leads.index') }}" class="a-btn a-btn-light a-btn-sm">View all <x-icon name="arrow-right" class="size-3.5" /></a>
            </div>

            {{-- Bar chart --}}
            <div class="px-5 pt-5">
                <div class="relative h-36">
                    <div class="absolute inset-0 flex flex-col justify-between" aria-hidden="true">
                        {{-- Axis: max, midpoint (only when it is a distinct whole number), zero --}}
                        @foreach([$maxDay, $maxDay >= 2 ? (int) round($maxDay / 2) : null, 0] as $tick)
                            <div class="flex items-center gap-2"><span class="w-5 text-right text-[0.625rem] tabular-nums text-slate-400">{{ $tick }}</span><span class="h-px flex-1 border-t border-dashed border-line"></span></div>
                        @endforeach
                    </div>
                    <div class="absolute inset-y-0 left-7 right-0 flex items-end gap-1.5 pb-[0.4rem]" role="img" aria-label="{{ $leads14 }} enquiries in the last 14 days">
                        @foreach($series as $i => $c)
                            <div class="group relative flex h-full flex-1 items-end">
                                <div class="w-full rounded-t-[4px] transition-colors {{ $c ? 'bg-brand/80 group-hover:bg-brand' : 'bg-line' }}" style="height: {{ $c ? max(6, $c / $maxDay * 100) : 3 }}%"></div>
                                <span class="pointer-events-none absolute -top-7 left-1/2 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-ink px-1.5 py-0.5 text-[0.625rem] font-medium text-white group-hover:block">{{ \Carbon\Carbon::parse($days[$i])->format('j M') }} · {{ $c }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="ml-7 mt-1.5 flex justify-between text-[0.625rem] text-slate-400">
                    <span>{{ \Carbon\Carbon::parse($days->first())->format('j M') }}</span><span>{{ \Carbon\Carbon::parse($days[7])->format('j M') }}</span><span>Today</span>
                </div>
            </div>

            <div class="mt-4 border-t border-line">
                @forelse($recentLeads as $lead)
                    <a href="{{ route('admin.leads.show', $lead->id) }}" class="flex items-center gap-3 border-b border-line/70 px-5 py-3 transition last:border-0 hover:bg-brand-50/40">
                        <span class="a-avatar size-8 text-[0.6875rem]">{{ mb_strtoupper(mb_substr($lead->name, 0, 1)) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[0.8125rem] font-semibold text-ink">{{ $lead->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $lead->service ?: 'General enquiry' }} · {{ $lead->phone }}</span>
                        </span>
                        <x-admin.status-badge :status="$lead->status" />
                        <span class="hidden w-24 text-right text-xs text-slate-500 sm:block">{{ $lead->created_at->diffForHumans(short: true) }}</span>
                    </a>
                @empty
                    <div class="a-empty"><span class="a-empty-icon"><x-icon name="inbox" class="size-5" /></span><p class="text-sm font-medium text-ink">No enquiries yet</p><p class="text-xs text-slate-500">New leads from the website forms appear here.</p></div>
                @endforelse
            </div>
        </div>
    @endcan

    <div class="grid grid-cols-1 content-start gap-6">
        {{-- ================= SEO health ================= --}}
        @if($seoReport)
            @php
                $checks = ['missing_title' => 'Missing meta title', 'missing_description' => 'Missing meta description', 'missing_h1' => 'Missing H1', 'missing_image' => 'No featured image', 'schema_disabled' => 'Schema disabled', 'noindex' => 'Noindex', 'missing_faqs' => 'No FAQs'];
                $issues = collect($checks)->map(fn ($label, $key) => collect($seoReport['rows'])->sum(fn ($row) => (int) $row[$key]));
                $totalIssues = $issues->sum();
            @endphp
            <div class="a-card">
                <div class="a-card-h">
                    <div>
                        <p class="a-card-t">SEO health</p>
                        <p class="a-card-d">{{ $totalIssues ? number_format($totalIssues).' open issues across content types' : 'No open issues' }}</p>
                    </div>
                    <a href="{{ route('admin.landing-pages.bulk-seo') }}" class="a-btn a-btn-light a-btn-sm">Bulk editor</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="a-table">
                        <thead><tr><th>Check</th>@foreach(array_keys($seoReport['rows']) as $h)<th class="text-right">{{ $h }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach($checks as $key => $label)
                                <tr>
                                    <td class="whitespace-nowrap font-medium text-slate-700">{{ $label }}</td>
                                    @foreach($seoReport['rows'] as $row)
                                        <td class="text-right tabular-nums">
                                            @if($row[$key])<span class="a-badge bg-amber-50 text-amber-700">{{ $row[$key] }}</span>@else<span class="text-slate-300">—</span>@endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="grid grid-cols-2 gap-3 border-t border-line p-4">
                    <a href="{{ route('admin.landing-pages.index', ['status' => 'draft']) }}" class="rounded-lg border border-line p-3 transition hover:border-amber-200 hover:bg-amber-50/50">
                        <p class="flex items-center gap-1.5 text-xs font-medium text-amber-700"><span class="a-dot"></span> Draft SEO pages</p>
                        <p class="mt-1 text-xl font-semibold tracking-[-0.02em] text-ink">{{ $seoReport['draft_programmatic'] }}</p>
                    </a>
                    <a href="{{ route('admin.redirects.index') }}" class="rounded-lg border border-line p-3 transition {{ $seoReport['broken_redirects'] ? 'hover:border-red-200 hover:bg-red-50/50' : 'hover:border-emerald-200 hover:bg-emerald-50/50' }}">
                        <p class="flex items-center gap-1.5 text-xs font-medium {{ $seoReport['broken_redirects'] ? 'text-red-700' : 'text-emerald-700' }}"><span class="a-dot"></span> Broken redirects</p>
                        <p class="mt-1 text-xl font-semibold tracking-[-0.02em] text-ink">{{ $seoReport['broken_redirects'] }}</p>
                    </a>
                </div>
            </div>
        @endif

        {{-- ================= Integrations ================= --}}
        <div class="a-card">
            <div class="a-card-h"><div><p class="a-card-t">Search & analytics</p><p class="a-card-d">Tracking and indexing status</p></div></div>
            @php $tracking = setting('gtm_id') ?: setting('ga4_id'); @endphp
            <ul class="divide-y divide-line/70">
                <li class="flex items-center gap-3 px-5 py-3.5">
                    <span class="a-stat-icon size-8 bg-mist text-slate-600"><x-icon name="chart" class="size-4" /></span>
                    <span class="min-w-0 flex-1"><span class="block text-[0.8125rem] font-medium text-ink">Analytics</span><span class="block truncate text-xs text-slate-500">{{ $tracking ? 'Tracking with '.$tracking : 'Add a GA4 or GTM ID to start tracking' }}</span></span>
                    <span class="a-badge {{ $tracking ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}"><span class="a-dot"></span>{{ $tracking ? 'Connected' : 'Not set' }}</span>
                    @can('settings.manage')<a href="{{ route('admin.settings.edit', 'analytics') }}" class="a-btn a-btn-ghost a-btn-sm" aria-label="Tracking settings"><x-icon name="settings" class="size-3.5" /></a>@endcan
                </li>
                <li class="flex items-center gap-3 px-5 py-3.5">
                    <span class="a-stat-icon size-8 bg-mist text-slate-600"><x-icon name="globe" class="size-4" /></span>
                    <span class="min-w-0 flex-1"><span class="block text-[0.8125rem] font-medium text-ink">XML sitemap</span><span class="block truncate text-xs text-slate-500">Auto-generated from published, indexable pages</span></span>
                    <span class="a-badge bg-emerald-50 text-emerald-700"><span class="a-dot"></span>Live</span>
                    <a href="{{ route('sitemap') }}" target="_blank" rel="noopener" class="a-btn a-btn-ghost a-btn-sm" aria-label="Open sitemap"><x-icon name="arrow-up-right" class="size-3.5" /></a>
                </li>
                <li class="flex items-center gap-3 px-5 py-3.5">
                    <span class="a-stat-icon size-8 bg-mist text-slate-600"><x-icon name="search" class="size-4" /></span>
                    <span class="min-w-0 flex-1"><span class="block text-[0.8125rem] font-medium text-ink">Search Console</span><span class="block truncate text-xs text-slate-500">{{ setting('gsc_verification') ? 'Site ownership verified' : 'Verify the site, then submit the sitemap' }}</span></span>
                    <span class="a-badge {{ setting('gsc_verification') ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}"><span class="a-dot"></span>{{ setting('gsc_verification') ? 'Verified' : 'Pending' }}</span>
                    <a href="https://search.google.com/search-console" target="_blank" rel="noopener" class="a-btn a-btn-ghost a-btn-sm" aria-label="Open Search Console"><x-icon name="arrow-up-right" class="size-3.5" /></a>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
