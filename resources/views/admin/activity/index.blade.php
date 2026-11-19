@extends('admin.layouts.app')

@section('title', 'Activity log')

@section('subtitle', 'An audit trail of changes made in the admin console.')

@section('content')
<div class="a-card overflow-hidden">
    <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-3">
        <select name="action" class="a-input w-auto" onchange="this.form.submit()" aria-label="Action"><option value="">All actions</option>@foreach($actions as $a)<option @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select>
    </form>
    <div class="overflow-x-auto">
    <table class="a-table">
        <thead><tr><th>When</th><th>User</th><th>Action</th><th>Subject</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
        @forelse($items as $log)
            <tr>
                <td class="whitespace-nowrap text-xs text-slate-500" title="{{ $log->created_at }}">{{ $log->created_at?->diffForHumans() }}</td>
                <td class="text-sm">{{ $log->user?->name ?? 'System' }}</td>
                <td><span class="a-badge bg-slate-100 text-slate-700">{{ $log->action }}</span></td>
                <td class="text-xs text-slate-600">{{ $log->subject_type ? $log->subject_type.' #'.$log->subject_id : '—' }}</td>
                <td class="max-w-md text-xs text-slate-600">{{ $log->description }} @if($log->properties)<code class="block truncate text-[0.65rem] text-slate-400">{{ json_encode($log->properties) }}</code>@endif</td>
                <td class="text-xs text-slate-400">{{ $log->ip }}</td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="a-empty"><span class="a-empty-icon"><x-icon name="activity" class="size-5" /></span><p class="text-sm font-medium text-ink">No activity recorded</p></div></td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
<div class="mt-4">{{ $items->links('admin.partials.pagination') }}</div>
@endsection
