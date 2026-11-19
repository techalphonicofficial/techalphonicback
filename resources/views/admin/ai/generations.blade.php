@extends('admin.layouts.app')

@section('title', 'AI writing log')

@section('subtitle', 'Every AI run on an SEO page: what it cost, whether it was applied, and how close the result is to sibling pages.')

@section('actions')
    @can('ai.configure')<a href="{{ route('admin.ai.settings') }}" class="a-btn a-btn-light"><x-icon name="settings" class="size-4" /> AI settings</a>@endcan
@endsection

@section('content')
@php $pendingOnPage = $items->contains(fn ($g) => $g->isPending()); @endphp
<div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach([
        ['Pages written this month', number_format($usage['succeeded']), 'check-circle', 'bg-emerald-50 text-emerald-600'],
        ['In progress', number_format($usage['pending']), 'refresh', 'bg-brand-50 text-brand'],
        ['Tokens this month', \App\Models\AiGeneration::compact($usage['tokens']), 'cpu', 'bg-slate-100 text-slate-600'],
        ['Est. cost this month', $usage['priced'] || $usage['cost'] ? '$'.number_format($usage['cost'], 2).($usage['budget'] ? ' / $'.number_format($usage['budget'], 0) : '') : '—', 'banknote', 'bg-amber/15 text-amber-700'],
    ] as [$label, $value, $icon, $tone])
        <div class="a-card flex items-center gap-3 p-4">
            <span class="a-stat-icon {{ $tone }}"><x-icon :name="$icon" class="size-4" /></span>
            <div class="min-w-0"><p class="truncate text-xs text-slate-500">{{ $label }}</p><p class="text-lg font-semibold text-ink">{{ $value }}</p></div>
        </div>
    @endforeach
</div>

<div class="a-card overflow-hidden">
    <form method="GET" class="flex flex-wrap items-center gap-3 border-b border-line px-4 py-3">
        <div class="a-seg">
            <a href="{{ route('admin.ai.generations', request()->except(['status', 'page'])) }}" class="a-seg-item {{ request('status') ? '' : 'active' }}">All</a>
            @foreach(\App\Models\AiGeneration::STATUSES as $k => $label)
                <a href="{{ route('admin.ai.generations', ['status' => $k] + request()->except(['status', 'page'])) }}" class="a-seg-item {{ request('status') === $k ? 'active' : '' }}">{{ $label }}<span class="a-seg-count">{{ $counts[$k] ?? 0 }}</span></a>
            @endforeach
        </div>
        <label class="a-chip cursor-pointer select-none"><input type="checkbox" name="similar" value="1" class="a-check size-3.5" @checked(request('similar')) onchange="this.form.submit()"> Similar to another page</label>
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        @if(request('batch'))
            <input type="hidden" name="batch" value="{{ request('batch') }}">
            <a href="{{ route('admin.ai.generations', request()->except(['batch', 'page'])) }}" class="a-chip hover:text-ink">This batch only <x-icon name="x" class="size-3" /></a>
        @endif
        @if($pendingOnPage)<span class="ml-auto flex items-center gap-1.5 text-xs text-slate-500"><span class="a-dot animate-pulse text-brand"></span> Refreshing while pages are being written</span>@endif
    </form>
    <div class="overflow-x-auto">
        <table class="a-table">
            <thead><tr><th>Page</th><th>Status</th><th>Model</th><th class="text-right">Tokens</th><th class="text-right">Cost</th><th>Similarity</th><th>When</th></tr></thead>
            <tbody>
            @forelse($items as $g)
                <tr>
                    <td class="max-w-sm">
                        @if($g->subject)
                            <a href="{{ route('admin.landing-pages.edit', $g->subject_id) }}" class="font-semibold text-ink hover:text-brand">{{ $g->subject->title }}</a>
                        @else
                            <span class="text-slate-400">Deleted page #{{ $g->subject_id }}</span>
                        @endif
                        @if($g->error)<p class="mt-0.5 text-xs text-red-600">{{ $g->error }}</p>@endif
                    </td>
                    <td><span class="a-badge {{ $g->statusClasses() }}">@if($g->isPending())<span class="a-dot animate-pulse"></span>@endif {{ $g->statusLabel() }}</span></td>
                    <td class="whitespace-nowrap text-xs"><span class="text-slate-500">{{ \App\Ai\AiSettings::PROVIDERS[$g->provider]['label'] ?? $g->provider }}</span><span class="block font-mono text-[0.6875rem] text-slate-700">{{ $g->model }}</span></td>
                    <td class="text-right tabular-nums text-slate-600">{{ $g->totalTokens() ? number_format($g->totalTokens()) : '—' }}</td>
                    <td class="text-right tabular-nums text-slate-600">{{ $g->cost_usd !== null ? '$'.number_format($g->cost_usd, 3) : '—' }}</td>
                    <td>
                        @if($g->similarity !== null)
                            @php $pct = round($g->similarity * 100); @endphp
                            <span class="a-badge {{ $pct >= 35 ? 'bg-red-50 text-red-700' : ($pct >= 20 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700') }}"
                                  title="Share of 5-word phrases shared with the most similar page for the same service">{{ $pct }}%</span>
                            @if($pct >= 20 && ($otherId = $g->output['similar_to'] ?? null))<a href="{{ route('admin.landing-pages.edit', $otherId) }}" class="ml-1 text-xs text-slate-500 hover:text-brand">vs #{{ $otherId }}</a>@endif
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap text-xs text-slate-500" title="{{ $g->created_at }}">{{ $g->created_at->diffForHumans() }}@if($g->duration_ms)<span class="block text-slate-400">{{ round($g->duration_ms / 1000) }}s · {{ $g->user?->name ?? 'System' }}</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="a-empty"><span class="a-empty-icon"><x-icon name="sparkles" class="size-5" /></span><p class="text-sm font-medium text-ink">No AI runs yet</p><p class="text-xs text-slate-500">Use “Write with AI” on an SEO page, or tick “Write content with AI” in the bulk generator.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $items->links('admin.partials.pagination') }}</div>

@if($pendingOnPage)
    <script>setTimeout(() => window.location.reload(), 5000);</script>
@endif
@endsection
