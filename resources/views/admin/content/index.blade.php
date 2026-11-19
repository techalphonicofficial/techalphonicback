@extends('admin.layouts.app')

@section('title', $ctrl['plural'])

@section('subtitle', number_format($items->total()).' '.strtolower($items->total() === 1 ? $ctrl['label'] : $ctrl['plural']).(request()->hasAny(['q', 'status', 'trashed']) || collect($filters)->keys()->contains(fn ($k) => request()->filled($k)) ? ' matching your filters' : ''))

@section('actions')
    @yield('index-actions')
    @if(auth()->user()->can($ctrl['resource'].'.create') || in_array($ctrl['resource'], ['redirects', 'schema']))
        <a href="{{ route($ctrl['route'].'.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="size-4" /> New {{ strtolower($ctrl['label']) }}</a>
    @endif
@endsection

@section('content')
@yield('above-table')

<div class="a-card overflow-hidden">
    {{-- Toolbar --}}
    <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-3">
        <label class="a-input-icon w-full sm:max-w-xs">
            <x-icon name="search" />
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ strtolower($ctrl['plural']) }}…" class="a-input" aria-label="Search">
        </label>
        @if($ctrl['publishable'])
            <select name="status" class="a-input w-auto" onchange="this.form.submit()" aria-label="Status">
                <option value="">All statuses</option>
                @foreach(\App\Enums\ContentStatus::options() as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
            </select>
        @endif
        @foreach($filters as $key => $f)
            <select name="{{ $key }}" class="a-input w-auto" onchange="this.form.submit()" aria-label="{{ $f['label'] }}">
                <option value="">{{ $f['label'] }}: all</option>
                @foreach($f['options'] as $k => $v)<option value="{{ $k }}" @selected((string) request($key) === (string) $k)>{{ $v }}</option>@endforeach
            </select>
        @endforeach
        @if($ctrl['softDeletes'])
            <label class="a-chip cursor-pointer select-none"><input type="checkbox" name="trashed" value="1" class="a-check size-3.5" @checked(request('trashed')) onchange="this.form.submit()"> <x-icon name="trash" class="size-3.5" /> Trash</label>
        @endif
        <button class="a-btn a-btn-light"><x-icon name="filter" class="size-3.5" /> Filter</button>
        @if(request()->hasAny(['q', 'status', 'trashed']) || collect($filters)->keys()->contains(fn ($k) => request()->filled($k)))
            <a href="{{ url()->current() }}" class="a-btn a-btn-ghost">Reset</a>
        @endif
    </form>
    <div class="overflow-x-auto">
        <table class="a-table">
            <thead><tr>
                @foreach($columns as [$label])<th>{{ $label }}</th>@endforeach
                <th class="text-right">Actions</th>
            </tr></thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        @foreach($columns as [$label, $path, $type])
                            @php $val = data_get($item, $path); @endphp
                            <td>
                                @switch($type)
                                    @case('title')
                                        <a href="{{ route($ctrl['route'].'.edit', $item->getKey()) }}" class="font-semibold text-ink hover:text-brand">{{ \Illuminate\Support\Str::limit(strip_tags((string) $val), 70) }}</a>
                                        @if(isset($item->slug) && method_exists($item, 'getUrlAttribute'))<span class="mt-0.5 block font-mono text-[0.6875rem] text-slate-400">{{ parse_url($item->url, PHP_URL_PATH) }}</span>@endif
                                        @break
                                    @case('status') <x-admin.status-badge :status="$val" /> @break
                                    @case('date') <span class="text-xs text-slate-500">{{ $val?->format('d M Y') }}</span> @break
                                    @case('bool') {!! $val ? '<span class="text-emerald-600">●</span>' : '<span class="text-slate-300">●</span>' !!} @break
                                    @case('badge') @if($val !== null && $val !== '')<span class="a-badge bg-slate-100 text-slate-600">{{ $val instanceof \BackedEnum ? $val->value : $val }}</span>@endif @break
                                    @case('seo')
                                        @php $s = $item->seo; @endphp
                                        <span class="flex gap-1 text-[0.65rem] font-bold">
                                            <span class="a-badge {{ $s?->meta_title ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}" title="Meta title">T</span>
                                            <span class="a-badge {{ $s?->meta_description ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}" title="Meta description">D</span>
                                            @if($s && ! $s->robots_index)<span class="a-badge bg-slate-200 text-slate-700" title="Noindex">NI</span>@endif
                                        </span>
                                        @break
                                    @default <span class="text-slate-600">{{ \Illuminate\Support\Str::limit((string) $val, 50) }}</span>
                                @endswitch
                            </td>
                        @endforeach
                        <td class="whitespace-nowrap text-right">
                            <span class="inline-flex items-center gap-1">
                                @if(isset($item->slug) && method_exists($item, 'getUrlAttribute') && method_exists($item, 'isPublished') && $item->isPublished())
                                    <a href="{{ $item->url }}" target="_blank" rel="noopener" class="a-btn a-btn-ghost a-btn-sm a-btn-icon" title="View on website" aria-label="View on website"><x-icon name="eye" class="size-3.5" /></a>
                                @endif
                                <a href="{{ route($ctrl['route'].'.edit', $item->getKey()) }}" class="a-btn a-btn-light a-btn-sm"><x-icon name="pen" class="size-3" /> Edit</a>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + 1 }}">
                        <div class="a-empty">
                            <span class="a-empty-icon"><x-icon name="inbox" class="size-5" /></span>
                            <p class="text-sm font-medium text-ink">{{ request()->hasAny(['q', 'status', 'trashed']) ? 'No results match your filters' : 'No '.strtolower($ctrl['plural']).' yet' }}</p>
                            <p class="text-xs text-slate-500">{{ request()->hasAny(['q', 'status', 'trashed']) ? 'Try a different search or reset the filters.' : 'Create the first one to get started.' }}</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $items->links('admin.partials.pagination') }}</div>
@endsection
