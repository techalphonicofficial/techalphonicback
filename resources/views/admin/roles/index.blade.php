@extends('admin.layouts.app')

@section('title', 'Roles & permissions')

@section('subtitle', 'Control what each team member can see and change.')
@section('actions')<a href="{{ route('admin.roles.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /> New role</a>@endsection

@section('content')
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($roles as $role)
        <a href="{{ route('admin.roles.edit', $role) }}" class="a-card a-card-b group flex items-start gap-3.5 transition hover:border-line-strong hover:shadow-[var(--shadow-pop)]">
            <span class="a-stat-icon {{ $role->name === 'Super Admin' ? 'bg-brand-50 text-brand' : 'bg-mist text-slate-500' }}"><x-icon name="shield" class="size-[18px]" /></span>
            <span class="min-w-0 flex-1">
                <span class="block font-semibold text-ink">{{ $role->name }}</span>
                <span class="mt-1 block text-xs text-slate-500">{{ $role->name === 'Super Admin' ? 'All permissions' : $role->permissions_count.' permissions' }} · {{ $role->users_count }} {{ $role->users_count === 1 ? 'user' : 'users' }}</span>
            </span>
            <x-icon name="chevron-right" class="mt-1 size-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-500" />
        </a>
    @endforeach
</div>
@endsection
