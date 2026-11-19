@extends('admin.layouts.app')

@section('title', 'Programmatic SEO → Create page')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="a-card">
        <div class="a-card-h"><p class="a-card-t">Step 1 — choose the combination</p></div>
        <form method="GET" action="{{ route('admin.landing-pages.create') }}" class="a-card-b grid gap-5" x-data="{ type: 'service_location' }">
            <div>
                <p class="a-label">Page type</p>
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach($types as $k => $label)
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold" :class="type === '{{ $k }}' ? 'border-brand bg-brand-50 text-ink' : 'border-line'">
                            <input type="radio" name="page_type" value="{{ $k }}" x-model="type" class="text-brand"> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
            <x-admin.field type="select" name="service_id" label="Service" :options="$services" required />
            <div x-show="type !== 'service_industry'"><x-admin.field type="select" name="location_id" label="Location" :options="$locations->mapWithKeys(fn ($l) => [$l->id => $l->name.($l->state ? ', '.$l->state : '')])" /></div>
            <div x-show="type !== 'service_location'"><x-admin.field type="select" name="industry_id" label="Industry" :options="$industries" /></div>
            <p class="rounded-lg bg-mist p-3 text-xs text-slate-600">Next you'll get a pre-filled draft: URL, H1, introduction, meta title & description from the service's programmatic template. Edit everything before publishing.</p>
            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.landing-pages.bulk') }}" class="a-btn a-btn-light">Bulk generate instead</a>
                <button class="a-btn a-btn-primary">Continue →</button>
            </div>
        </form>
    </div>
</div>
@endsection
