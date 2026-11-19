@extends('admin.layouts.app')

@section('title', 'Legacy sitemap import')

@section('content')
<div class="grid gap-6 xl:grid-cols-[1fr_360px]">
    <div class="min-w-0">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach(['' => 'All', 'unmapped' => 'Unmapped', 'mapped' => 'Mapped', 'redirected' => 'Redirected', 'ignored' => 'Ignored'] as $k => $v)
                <a href="{{ route('admin.sitemap-import.index', $k ? ['status' => $k] : []) }}" class="a-btn a-btn-sm {{ request('status', '') === $k ? 'a-btn-dark' : 'a-btn-light' }}">{{ $v }} <span class="opacity-60">{{ $k ? ($counts[$k] ?? 0) : $counts->sum() }}</span></a>
            @endforeach
            <form method="GET" class="ml-auto"><input type="hidden" name="status" value="{{ request('status') }}"><input type="search" name="q" value="{{ request('q') }}" placeholder="Filter URLs…" class="a-input"></form>
        </div>
        <div class="a-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="a-table">
                    <thead><tr><th>Old URL</th><th>Type</th><th>New URL</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($items as $row)
                        <tr>
                            <td class="font-mono text-xs"><a href="{{ url($row->old_url) }}" target="_blank" class="hover:text-brand">{{ $row->old_url }}</a></td>
                            <td colspan="4">
                                <form method="POST" action="{{ route('admin.sitemap-import.update', $row->id) }}" class="grid grid-cols-[110px_1fr_120px_auto] items-center gap-2">
                                    @csrf @method('PUT')
                                    <input name="page_type" value="{{ $row->page_type }}" class="a-input text-xs">
                                    <input name="new_url" value="{{ $row->new_url }}" class="a-input font-mono text-xs" placeholder="/new-url/">
                                    <select name="status" class="a-input text-xs">@foreach(['unmapped', 'mapped', 'redirected', 'ignored'] as $s)<option @selected($row->status === $s)>{{ $s }}</option>@endforeach</select>
                                    <button class="a-btn a-btn-light a-btn-sm">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center text-slate-500">Import your old sitemap to start mapping URLs.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $items->links('admin.partials.pagination') }}</div>
    </div>

    <aside class="grid content-start gap-4">
        <form method="POST" action="{{ route('admin.sitemap-import.import') }}" enctype="multipart/form-data" class="a-card">
            @csrf
            <div class="a-card-h"><p class="a-card-t">1. Import old sitemap</p></div>
            <div class="a-card-b grid gap-3">
                <input type="file" name="file" accept=".xml,text/xml" class="a-input">
                <p class="text-center text-xs text-slate-400">or paste XML</p>
                <textarea name="xml" rows="5" class="a-input font-mono text-xs" placeholder="<urlset>…</urlset>"></textarea>
                <button class="a-btn a-btn-dark">Import URLs</button>
                <p class="a-help">URLs that already exist on the new site are marked “mapped” automatically.</p>
            </div>
        </form>
        <div class="a-card a-card-b">
            <p class="a-card-t">2. Map unmapped URLs</p>
            <p class="mt-1 text-sm text-slate-600">Set a new URL for each old page that changed (or mark it ignored). Create the page first if it should keep its URL.</p>
        </div>
        <form method="POST" action="{{ route('admin.sitemap-import.redirects') }}" class="a-card a-card-b" onsubmit="return confirm('Create 301 redirects for all mapped URLs that changed?')">
            @csrf
            <p class="a-card-t">3. Create 301 redirects</p>
            <p class="mt-1 text-sm text-slate-600">Creates a permanent redirect for every mapped URL whose new path differs.</p>
            <button class="a-btn a-btn-primary mt-3 w-full">Create redirects</button>
        </form>
    </aside>
</div>
@endsection
