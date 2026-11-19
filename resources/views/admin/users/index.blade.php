@extends('admin.layouts.app')

@section('title', 'Users')

@section('subtitle', 'People with access to the admin console.')
@section('actions')<a href="{{ route('admin.users.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /> New user</a>@endsection

@section('content')
<div class="a-card overflow-hidden">
    <div class="overflow-x-auto">
    <table class="a-table">
        <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
        <tbody>
        @foreach($items as $u)
            <tr>
                <td>
                    <span class="flex items-center gap-3">
                        <span class="a-avatar size-8 text-[0.6875rem]">{{ collect(explode(' ', $u->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') }}</span>
                        <span><span class="block font-semibold text-ink">{{ $u->name }}</span><span class="block text-xs text-slate-500">{{ $u->email }}</span></span>
                    </span>
                </td>
                <td>@foreach($u->roles as $r)<span class="a-badge bg-slate-100 text-slate-700">{{ $r->name }}</span>@endforeach</td>
                <td>{!! $u->is_active ? '<span class="a-badge bg-emerald-50 text-emerald-700"><span class="a-dot"></span>Active</span>' : '<span class="a-badge bg-slate-100 text-slate-500"><span class="a-dot"></span>Disabled</span>' !!}</td>
                <td class="text-xs text-slate-500">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                <td class="text-right">@can('update', $u)<a href="{{ route('admin.users.edit', $u) }}" class="a-btn a-btn-light a-btn-sm"><x-icon name="pen" class="size-3" /> Edit</a>@endcan</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
<div class="mt-4">{{ $items->links('admin.partials.pagination') }}</div>
@endsection
