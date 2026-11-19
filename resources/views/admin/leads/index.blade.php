@extends('admin.layouts.app')

@section('title', 'Leads')

@section('subtitle', 'Enquiries captured by the website forms, newest first.')

@section('actions')
    @can('leads.export')<a href="{{ route('admin.leads.export', request()->query()) }}" class="a-btn a-btn-light"><x-icon name="database" class="size-4" /> Export CSV</a>@endcan
@endsection

@section('content')
<nav class="a-seg mb-4" aria-label="Lead status">
    <a href="{{ route('admin.leads.index', request()->except('status', 'page')) }}" class="a-seg-item {{ request('status') ? '' : 'active' }}">All <span class="a-seg-count">{{ $counts->sum() }}</span></a>
    @foreach(\App\Enums\LeadStatus::cases() as $s)
        <a href="{{ route('admin.leads.index', array_merge(request()->except('page'), ['status' => $s->value])) }}" class="a-seg-item {{ request('status') === $s->value ? 'active' : '' }}">{{ $s->label() }} <span class="a-seg-count">{{ $counts[$s->value] ?? 0 }}</span></a>
    @endforeach
</nav>

<div class="a-card overflow-hidden">
    <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-3">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <label class="a-input-icon w-full sm:max-w-xs">
            <x-icon name="search" />
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, email, phone, company…" class="a-input" aria-label="Search leads">
        </label>
        <select name="service" class="a-input w-auto" aria-label="Service"><option value="">All services</option>@foreach($services as $s)<option @selected(request('service') === $s)>{{ $s }}</option>@endforeach</select>
        <input type="date" name="from" value="{{ request('from') }}" class="a-input w-auto" aria-label="From">
        <span class="text-xs text-slate-400">to</span>
        <input type="date" name="to" value="{{ request('to') }}" class="a-input w-auto" aria-label="To">
        <button class="a-btn a-btn-light"><x-icon name="filter" class="size-3.5" /> Filter</button>
        @if(request()->hasAny(['q', 'service', 'from', 'to']))<a href="{{ route('admin.leads.index', request()->only('status')) }}" class="a-btn a-btn-ghost">Reset</a>@endif
    </form>
    <div class="overflow-x-auto">
        <table class="a-table">
            <thead><tr><th>Lead</th><th>Contact</th><th>Service</th><th>Source</th><th>Status</th><th>Assigned</th><th>Received</th></tr></thead>
            <tbody>
            @forelse($items as $lead)
                <tr>
                    <td>
                        <a href="{{ route('admin.leads.show', $lead->id) }}" class="flex items-center gap-3">
                            <span class="a-avatar size-8 text-[0.6875rem]">{{ mb_strtoupper(mb_substr($lead->name, 0, 1)) }}</span>
                            <span><span class="block font-semibold text-ink hover:text-brand">{{ $lead->name }}</span>@if($lead->company)<span class="block text-xs text-slate-500">{{ $lead->company }}</span>@endif</span>
                        </a>
                    </td>
                    <td class="text-xs"><a href="tel:{{ preg_replace('/[^\d+]/', '', $lead->phone) }}" class="block font-medium text-ink">{{ $lead->phone }}</a><span class="text-slate-500">{{ $lead->email }}</span></td>
                    <td class="text-slate-600">{{ $lead->service ?: '—' }}</td>
                    <td class="max-w-48 truncate text-xs text-slate-500" title="{{ $lead->source_page }}">{{ parse_url((string) $lead->source_page, PHP_URL_PATH) ?: '—' }}</td>
                    <td><x-admin.status-badge :status="$lead->status" /></td>
                    <td class="text-xs text-slate-600">{{ $lead->assignee?->name ?? '—' }}</td>
                    <td class="whitespace-nowrap text-xs text-slate-500" title="{{ $lead->created_at }}">{{ $lead->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="a-empty"><span class="a-empty-icon"><x-icon name="inbox" class="size-5" /></span><p class="text-sm font-medium text-ink">No leads match</p><p class="text-xs text-slate-500">Try another status or reset the filters.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $items->links('admin.partials.pagination') }}</div>
@endsection
