@extends('admin.layouts.app')

@section('title', 'Bulk SEO editor')

@section('content')
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <select name="status" class="a-input w-auto" onchange="this.form.submit()"><option value="">All statuses</option>@foreach(\App\Enums\ContentStatus::options() as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
    @foreach($filters as $key => $f)
        <select name="{{ $key }}" class="a-input w-auto" onchange="this.form.submit()"><option value="">{{ $f['label'] }}: all</option>@foreach($f['options'] as $k => $v)<option value="{{ $k }}" @selected((string) request($key) === (string) $k)>{{ $v }}</option>@endforeach</select>
    @endforeach
</form>
<p class="mb-3 text-sm text-slate-600">Edit up to 50 pages at once. Variables like <code>@{{location_name}}</code> are allowed. Counters show length guidance (title 50–60, description 140–160).</p>
<form method="POST" action="{{ route('admin.landing-pages.bulk-seo.update') }}" class="a-card overflow-hidden">
    @csrf @method('PUT')
    <div class="overflow-x-auto">
        <table class="a-table">
            <thead><tr><th class="min-w-48">Page</th><th class="min-w-72">Meta title</th><th class="min-w-80">Meta description</th><th class="min-w-48">Focus keyword</th><th class="min-w-48">Canonical</th><th>Index</th></tr></thead>
            <tbody>
            @foreach($items as $p)
                <tr class="align-top">
                    <td><a href="{{ route('admin.landing-pages.edit', $p->id) }}" class="font-semibold text-ink hover:text-brand">{{ $p->title }}</a><x-admin.status-badge :status="$p->status" class="mt-1" /></td>
                    <td x-data="counter(50, 60, @js((string) $p->seo?->meta_title))"><input name="rows[{{ $p->id }}][meta_title]" x-model="text" class="a-input"><span class="text-[0.65rem]" :class="tone" x-text="len"></span></td>
                    <td x-data="counter(140, 160, @js((string) $p->seo?->meta_description))"><textarea name="rows[{{ $p->id }}][meta_description]" rows="2" x-model="text" class="a-input"></textarea><span class="text-[0.65rem]" :class="tone" x-text="len"></span></td>
                    <td><input name="rows[{{ $p->id }}][focus_keyword]" value="{{ $p->seo?->focus_keyword }}" class="a-input"></td>
                    <td><input name="rows[{{ $p->id }}][canonical_url]" value="{{ $p->seo?->canonical_url }}" class="a-input" placeholder="self"></td>
                    <td class="text-center"><input type="hidden" name="rows[{{ $p->id }}][robots_index]" value="0"><input type="checkbox" name="rows[{{ $p->id }}][robots_index]" value="1" class="a-check" @checked($p->seo?->robots_index ?? true)></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex items-center justify-between border-t border-line p-4">
        {{ $items->links('admin.partials.pagination') }}
        <button class="a-btn a-btn-primary">Save all</button>
    </div>
</form>
@endsection
