@extends('admin.layouts.app')

@section('title', 'Programmatic SEO → Bulk generator')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-5 rounded-xl border border-amber/40 bg-amber/10 p-4 text-sm text-ink">
        <p class="font-semibold">Pages are created as drafts with noindex — nothing is published automatically.</p>
        <p class="mt-1 text-slate-600">After generating, open each draft, add unique local/industry content and FAQs, then publish and enable indexing. Existing combinations are skipped.</p>
    </div>
    <form method="POST" action="{{ route('admin.landing-pages.bulk.generate') }}" class="a-card" x-data="{ type: '{{ old('page_type', 'service_location') }}', locs: [], inds: [] }">
        @csrf
        <div class="a-card-b grid gap-5">
            <div class="grid gap-2 sm:grid-cols-3">
                @foreach($types as $k => $label)
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold" :class="type === '{{ $k }}' ? 'border-brand bg-brand-50' : 'border-line'">
                        <input type="radio" name="page_type" value="{{ $k }}" x-model="type"> {{ $label }}
                    </label>
                @endforeach
            </div>
            <x-admin.field type="select" name="service_id" label="Service" :options="$services" required />

            <div x-show="type !== 'service_industry'">
                <div class="mb-2 flex items-center justify-between"><p class="a-label !mb-0">Locations (<span x-text="locs.length"></span> selected)</p>
                    <button type="button" class="text-xs font-semibold text-brand" @click="locs = locs.length ? [] : @js($locations->pluck('id')->map(fn ($i) => (string) $i))">Toggle all</button></div>
                <div class="grid max-h-72 grid-cols-2 gap-1 overflow-y-auto rounded-xl border border-line p-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($locations as $l)
                        <label class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-mist"><input type="checkbox" name="location_ids[]" value="{{ $l->id }}" x-model="locs" class="a-check"> {{ $l->name }}</label>
                    @endforeach
                </div>
            </div>

            <div x-show="type !== 'service_location'">
                <div class="mb-2 flex items-center justify-between"><p class="a-label !mb-0">Industries (<span x-text="inds.length"></span> selected)</p>
                    <button type="button" class="text-xs font-semibold text-brand" @click="inds = inds.length ? [] : @js(collect($industries)->keys()->map(fn ($i) => (string) $i))">Toggle all</button></div>
                <div class="grid grid-cols-2 gap-1 rounded-xl border border-line p-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($industries as $id => $name)
                        <label class="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-mist"><input type="checkbox" name="industry_ids[]" value="{{ $id }}" x-model="inds" class="a-check"> {{ $name }}</label>
                    @endforeach
                </div>
            </div>

            @can('ai.generate')
                @php $ai = app(\App\Ai\AiManager::class)->settings(); @endphp
                <div class="rounded-xl border border-line bg-mist/50 p-4">
                    @if($ai->ready())
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" name="ai_write" value="1" class="a-check mt-0.5" @checked(old('ai_write'))>
                            <span class="text-sm"><span class="flex items-center gap-1.5 font-semibold text-ink"><x-icon name="sparkles" class="size-4 text-brand" /> Write unique content for each page with AI</span>
                                <span class="mt-0.5 block text-xs text-slate-500">Uses {{ \App\Ai\AiSettings::PROVIDERS[$ai->provider()]['label'] }} · <span class="font-mono">{{ $ai->model() }}</span>. Pages are written one by one in the background and stay drafts with noindex.@if($ai->monthlyBudget()) Stops at the ${{ number_format($ai->monthlyBudget(), 0) }} monthly budget.@endif</span></span>
                        </label>
                    @else
                        <p class="flex items-center gap-2 text-xs text-slate-500"><x-icon name="sparkles" class="size-4" /> AI writing can fill these drafts with unique content.@can('ai.configure') <a href="{{ route('admin.ai.settings') }}" class="font-semibold text-brand">Set up AI</a>@endcan</p>
                    @endif
                </div>
            @endcan

            <p class="text-sm text-slate-600">Will create up to
                <strong x-text="type === 'service_location' ? locs.length : (type === 'service_industry' ? inds.length : locs.length * inds.length)"></strong> draft pages.</p>
        </div>
        <div class="flex justify-end border-t border-line p-4"><button class="a-btn a-btn-primary" onclick="return confirm('Generate draft pages now?')">Generate drafts</button></div>
    </form>
</div>
@endsection
