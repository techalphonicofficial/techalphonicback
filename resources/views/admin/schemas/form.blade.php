@extends('admin.content.form', ['tabs' => ['basic' => 'Schema template', 'global' => 'Global schema preview']])

@section('tab-basic')
<div class="grid gap-4 md:grid-cols-3">
    <x-admin.field name="name" label="Name" :value="$item->name" required />
    <x-admin.field type="select" name="type" label="Schema type" :value="$item->type" :options="array_combine($types, $types)" :empty="false" />
    <x-admin.field type="select" name="applies_to" label="Applies to" :value="$item->applies_to" :options="array_combine($appliesTo, array_map(fn ($a) => ucwords(str_replace('_', ' ', $a)), $appliesTo))" :empty="false" />
    <x-admin.field type="textarea" name="json_template" label="JSON-LD template" :value="$item->json_template" rows="16" class="font-mono md:col-span-3" required />
    <p class="text-xs text-slate-500 md:col-span-3">Variables: @foreach($variables as $v)<code class="mx-0.5 rounded bg-mist px-1">{{ '{'.'{'.$v.'}'.'}' }}</code>@endforeach. Output is validated JSON; invalid templates are rejected and never rendered. Don't add Organization/WebSite/Breadcrumb/FAQPage here — those are generated automatically.</p>
    <div class="pt-2"><x-admin.field type="checkbox" name="is_active" label="Active" :value="$item->is_active ?? true" /></div>
    <x-admin.field type="number" name="sort_order" label="Order" :value="$item->sort_order ?? 0" />
</div>
@endsection

@section('tab-global')
<p class="mb-2 text-sm text-slate-600">Organization / LocalBusiness and WebSite schema are generated from <a href="{{ route('admin.settings.edit', 'contact') }}" class="font-semibold text-brand">Contact</a> and <a href="{{ route('admin.settings.edit', 'seo') }}" class="font-semibold text-brand">SEO settings</a>:</p>
<pre class="max-h-[500px] overflow-auto rounded-xl bg-ink p-4 text-xs text-emerald-300">{{ json_encode(json_decode($globalPreview), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
@endsection
