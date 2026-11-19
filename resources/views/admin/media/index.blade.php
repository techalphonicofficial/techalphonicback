@extends('admin.layouts.app')

@section('title', 'Media library')

@section('subtitle', 'Images are re-encoded on upload (metadata stripped) with WebP and responsive sizes generated automatically. Max '.round(config('site.max_upload_kb') / 1024).' MB per file.')

@section('actions')
    @can('media.create')
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">
            @csrf
            <label class="a-btn a-btn-primary cursor-pointer"><x-icon name="plus" class="size-4" /> Upload files
                <input type="file" name="files[]" multiple class="hidden" accept="image/*,application/pdf,video/mp4" onchange="this.form.submit()">
            </label>
        </form>
    @endcan
@endsection

@section('content')
<form method="GET" class="a-card mb-5 flex flex-wrap items-center gap-2 px-4 py-3">
    <label class="a-input-icon w-full sm:max-w-xs"><x-icon name="search" /><input type="search" name="q" value="{{ request('q') }}" placeholder="Search filename, alt or title…" class="a-input" aria-label="Search media"></label>
    <select name="type" class="a-input w-auto" onchange="this.form.submit()" aria-label="Type">
        <option value="">All types</option>
        @foreach(['image' => 'Images', 'video' => 'Videos', 'document' => 'Documents'] as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach
    </select>
    <label class="a-chip cursor-pointer select-none"><input type="checkbox" name="missing_alt" value="1" class="a-check size-3.5" @checked(request('missing_alt')) onchange="this.form.submit()"> Missing alt text</label>
</form>

<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6" x-data="{ edit: null }">
    @forelse($items as $m)
        <div class="a-card overflow-hidden">
            <button type="button" class="block aspect-square w-full bg-mist" @click="edit = {{ $m->id }}">
                @if($m->isImage())<img src="{{ isset($m->variants['sm']) ? $m->urlFor($m->variants['sm']) : ($m->webp_url ?: $m->url) }}" alt="{{ $m->alt }}" loading="lazy" class="size-full object-cover">
                @else<span class="grid size-full place-items-center text-xs font-semibold uppercase text-slate-500">{{ pathinfo($m->filename, PATHINFO_EXTENSION) }}</span>@endif
            </button>
            <div class="p-2">
                <p class="truncate text-xs font-medium text-ink">{{ $m->filename }}</p>
                <p class="flex justify-between text-[0.65rem] text-slate-400"><span>#{{ $m->id }} · {{ $m->humanSize() }}</span>@if($m->isImage() && ! $m->alt)<span class="font-semibold text-amber-600">no alt</span>@endif</p>
            </div>

            {{-- Edit drawer --}}
            <div x-show="edit === {{ $m->id }}" x-cloak class="fixed inset-0 z-50 flex justify-end bg-ink/50" @keydown.escape.window="edit = null">
                <div class="h-full w-full max-w-md overflow-y-auto bg-white p-6 shadow-2xl" @click.outside="edit = null">
                    <div class="mb-4 flex items-center justify-between"><p class="font-semibold text-ink">Media #{{ $m->id }}</p><button type="button" @click="edit = null"><x-icon name="x" class="size-5" /></button></div>
                    @if($m->isImage())<img src="{{ $m->webp_url ?: $m->url }}" alt="" class="mb-4 w-full rounded-lg border border-line">@endif
                    <p class="mb-4 break-all text-xs text-slate-500">{{ $m->url }}<br>{{ $m->width ? $m->width.'×'.$m->height.' · ' : '' }}{{ $m->mime_type }} · {{ $m->humanSize() }} · uploaded {{ $m->created_at->format('d M Y') }}</p>
                    @can('media.update')
                        <form method="POST" action="{{ route('admin.media.update', $m->id) }}" class="grid gap-3">
                            @csrf @method('PUT')
                            <x-admin.field name="alt" label="Alt text" :value="$m->alt" help="Describe the image for screen readers & SEO." />
                            <x-admin.field name="title" label="Title" :value="$m->title" />
                            <x-admin.field name="caption" label="Caption" :value="$m->caption" />
                            <x-admin.field type="textarea" name="description" label="Description" :value="$m->description" rows="2" />
                            <button class="a-btn a-btn-primary">Save details</button>
                        </form>
                        @if(in_array($m->mime_type, ['image/jpeg', 'image/png', 'image/webp']) && $m->width && $m->height)
                            <div class="mt-5 border-t border-line pt-4">
                                <p class="a-label">Crop (centered)</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['16:9' => 16 / 9, '4:3' => 4 / 3, '1:1' => 1, '1200×630 (OG)' => 1200 / 630] as $label => $ratio)
                                        @php
                                            $w = $m->width; $h = (int) round($w / $ratio);
                                            if ($h > $m->height) { $h = $m->height; $w = (int) round($h * $ratio); }
                                        @endphp
                                        <form method="POST" action="{{ route('admin.media.crop', $m->id) }}" onsubmit="return confirm('Crop this image? This changes the file everywhere it is used.')">
                                            @csrf
                                            <input type="hidden" name="x" value="{{ intdiv($m->width - $w, 2) }}"><input type="hidden" name="y" value="{{ intdiv($m->height - $h, 2) }}">
                                            <input type="hidden" name="width" value="{{ $w }}"><input type="hidden" name="height" value="{{ $h }}">
                                            <button class="a-btn a-btn-light a-btn-sm">{{ $label }}</button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('admin.media.replace', $m->id) }}" enctype="multipart/form-data" class="mt-5 border-t border-line pt-4">
                            @csrf
                            <p class="a-label">Replace file (keeps the same ID everywhere)</p>
                            <input type="file" name="file" class="a-input" required>
                            <button class="a-btn a-btn-light mt-2 w-full">Replace</button>
                        </form>
                    @endcan
                    @can('media.delete')
                        <form method="POST" action="{{ route('admin.media.destroy', $m->id) }}" class="mt-5" onsubmit="return confirm('Permanently delete this file? Pages using it will lose the image.')">
                            @csrf @method('DELETE')
                            <button class="a-btn a-btn-danger w-full">Delete permanently</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    @empty
        <p class="col-span-full rounded-2xl border border-dashed border-line p-16 text-center text-slate-500">No files yet. Upload your first image.</p>
    @endforelse
</div>
<div class="mt-6">{{ $items->links('admin.partials.pagination') }}</div>
@endsection
