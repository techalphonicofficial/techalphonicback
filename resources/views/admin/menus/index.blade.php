@extends('admin.layouts.app')

@section('title', 'Menus')

@section('subtitle', 'Header, mega menu, mobile and footer navigation. Linked items follow slug changes and hide when their page is unpublished.')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($locations as $key => $label)
        @php $menu = $menus[$key] ?? null; @endphp
        <a href="{{ route('admin.menus.edit', $key) }}" class="a-card a-card-b group flex items-start gap-3.5 transition hover:border-line-strong hover:shadow-[var(--shadow-pop)]">
            <span class="a-stat-icon bg-mist text-slate-500"><x-icon name="menu" class="size-[18px]" /></span>
            <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2"><span class="font-semibold text-ink">{{ $label }}</span>@if($menu && ! $menu->is_active)<span class="a-badge bg-slate-100 text-slate-500">Disabled</span>@endif</span>
                <span class="mt-1 block text-xs text-slate-500">{{ $menu ? $menu->items_count.' items' : 'Not created yet' }} · <code class="text-[0.6875rem]">{{ $key }}</code></span>
            </span>
            <x-icon name="chevron-right" class="mt-1 size-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-500" />
        </a>
    @endforeach
</div>
@endsection
