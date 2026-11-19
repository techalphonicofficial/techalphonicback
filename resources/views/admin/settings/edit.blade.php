@extends('admin.layouts.app')

@section('title', 'Settings — '.ucfirst($group))

@section('content')
<nav class="mb-5 flex flex-wrap gap-1 border-b border-line">
    @foreach($groups as $g)
        <a href="{{ route('admin.settings.edit', $g) }}" class="a-tab {{ $g === $group ? 'border-brand text-ink' : 'border-transparent text-slate-500 hover:text-ink' }}">{{ ucfirst($g) }}</a>
    @endforeach
</nav>

@if($group === 'social')
    <form method="POST" action="{{ route('admin.settings.social') }}" class="a-card max-w-3xl">
        @csrf @method('PUT')
        <div class="a-card-b" x-data="repeater(@js($socials->map(fn ($s) => ['platform' => $s->platform, 'url' => $s->url, 'icon' => $s->icon, 'is_active' => $s->is_active])->values()), { platform: '', url: '', icon: '', is_active: true })">
            <div class="mb-3 flex items-center justify-between"><p class="a-card-t">Social profiles</p><button type="button" class="a-btn a-btn-light a-btn-sm" @click="add()">+ Add</button></div>
            <template x-for="(row, i) in rows" :key="i">
                <div class="mb-2 grid gap-2 sm:grid-cols-[140px_1fr_140px_auto_auto]">
                    <input class="a-input" :name="`links[${i}][platform]`" x-model="row.platform" placeholder="LinkedIn">
                    <input class="a-input" :name="`links[${i}][url]`" x-model="row.url" placeholder="https://">
                    <select class="a-input" :name="`links[${i}][icon]`" x-model="row.icon"><option value="">Auto</option>@foreach(['facebook', 'instagram', 'linkedin', 'twitter', 'youtube', 'pinterest', 'whatsapp', 'globe'] as $ic)<option value="{{ $ic }}">{{ $ic }}</option>@endforeach</select>
                    <label class="flex items-center gap-1 text-xs"><input type="hidden" :name="`links[${i}][is_active]`" :value="row.is_active ? 1 : 0"><input type="checkbox" class="a-check" x-model="row.is_active"> Active</label>
                    <button type="button" class="text-red-500" @click="remove(i)">✕</button>
                </div>
            </template>
        </div>
        <div class="flex justify-end border-t border-line p-4"><button class="a-btn a-btn-primary">Save</button></div>
    </form>
@else
    <form method="POST" action="{{ route('admin.settings.update', $group) }}" class="a-card max-w-4xl" data-guard>
        @csrf @method('PUT')
        <div class="a-card-b grid gap-5 md:grid-cols-2">
            @foreach($fields as $key => $def)
                @php
                    [$label, $type] = $def;
                    $help = $def[2] ?? null;
                    $options = $def[3] ?? [];
                    $val = $values[$key] ?? null;
                    $restricted = in_array($key, ['head_scripts', 'body_scripts'], true) && ! auth()->user()->hasRole('Super Admin');
                @endphp
                @if($restricted) @continue @endif
                @switch($type)
                    @case('image')
                        <x-admin.media-picker :name="$key" :label="$label" :media="$val ? \App\Models\Media::find($val) : null" :help="$help" />
                        @break
                    @case('boolean')
                        <div class="pt-6"><x-admin.field type="checkbox" :name="$key" :label="$label" :value="filter_var($val, FILTER_VALIDATE_BOOLEAN)" :help="$help" /></div>
                        @break
                    @case('textarea')
                    @case('code')
                        <x-admin.field type="textarea" :name="$key" :label="$label" :value="$val" :help="$help" rows="{{ $type === 'code' ? 8 : 3 }}" class="md:col-span-2 {{ $type === 'code' ? 'font-mono' : '' }}" />
                        @break
                    @case('select')
                        <x-admin.field type="select" :name="$key" :label="$label" :value="$val" :options="$options" :help="$help" :empty="false" />
                        @break
                    @case('color')
                        <x-admin.field type="color" :name="$key" :label="$label" :value="$val" :help="$help" />
                        @break
                    @default
                        <x-admin.field :type="in_array($type, ['email', 'url', 'number']) ? $type : 'text'" :name="$key" :label="$label" :value="$val" :help="$help" />
                @endswitch
            @endforeach
        </div>

        @if($group === 'forms')
            <div class="mx-5 mb-5 rounded-xl bg-mist p-4 text-sm text-slate-600">
                <p class="font-semibold text-ink">Email delivery (SMTP)</p>
                <p class="mt-1">SMTP credentials are read from <code>.env</code> (MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS) and never stored in the database.</p>
                <p class="mt-2 text-xs">Current: mailer <strong>{{ $mail['mailer'] }}</strong> · host <strong>{{ $mail['host'] ?: '—' }}</strong> · port <strong>{{ $mail['port'] ?: '—' }}</strong> · from <strong>{{ $mail['from'] ?: '—' }}</strong></p>
            </div>
        @endif
        @if($group === 'seo')
            <div class="mx-5 mb-5 rounded-xl bg-mist p-4 text-sm text-slate-600">
                Live files: <a href="{{ route('robots') }}" target="_blank" class="font-semibold text-brand">/robots.txt ↗</a> · <a href="{{ route('sitemap') }}" target="_blank" class="font-semibold text-brand">/sitemap.xml ↗</a>
            </div>
        @endif
        <div class="flex justify-end border-t border-line p-4"><button class="a-btn a-btn-primary">Save settings</button></div>
    </form>
@endif
@endsection
