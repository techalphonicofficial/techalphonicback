@extends('admin.content.form', ['tabs' => ['basic' => 'Redirect']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-2">
    <x-admin.field name="old_url" label="Old URL (path)" :value="$item->old_url" required placeholder="/old-page/" help="Path only — the domain is ignored." />
    <x-admin.field name="new_url" label="New URL" :value="$item->new_url" placeholder="/new-page/ or https://…" help="Leave empty for 410 Gone." />
    <x-admin.field type="select" name="status_code" label="Type" :value="$item->status_code ?? 301" :options="[301 => '301 — Permanent (SEO)', 302 => '302 — Temporary', 410 => '410 — Gone']" :empty="false" />
    <div class="pt-7"><x-admin.field type="checkbox" name="is_active" label="Active" :value="$item->is_active ?? true" /></div>
    @if($item->exists)
        <p class="text-xs text-slate-500 md:col-span-2">{{ number_format($item->hits) }} hits · last used {{ $item->last_hit_at?->diffForHumans() ?? 'never' }} · source: {{ $item->source }}</p>
    @endif
</div>
@endsection
