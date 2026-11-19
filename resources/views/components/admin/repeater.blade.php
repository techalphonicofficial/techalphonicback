@props([
    'name',
    'label',
    'rows' => [],
    'fields' => ['title' => 'Title', 'text' => 'Text'], // key => label; suffix ":textarea" / ":icon" / ":checkbox" for input type
    'help' => null,
    'addLabel' => 'Add row',
])
@php
    $parsed = collect($fields)->map(function ($label, $key) {
        [$k, $t] = array_pad(explode(':', $key), 2, 'text');
        return ['key' => $k, 'type' => $t, 'label' => $label];
    })->values();
    $blank = $parsed->mapWithKeys(fn ($f) => [$f['key'] => ''])->all();
    $rows = old(str_replace(['[', ']'], ['.', ''], $name), $rows) ?? [];
@endphp
<div x-data="repeater(@js(array_values((array) $rows)), @js($blank))">
    <div class="mb-2 flex items-center justify-between">
        <p class="a-label !mb-0">{{ $label }}</p>
        <button type="button" class="a-btn a-btn-light a-btn-sm" @click="add()"><x-icon name="plus" class="size-3.5" /> {{ $addLabel }}</button>
    </div>
    @if($help)<p class="a-help -mt-1 mb-2">{{ $help }}</p>@endif
    <div class="grid gap-2">
        <template x-for="(row, i) in rows" :key="i">
            <div class="flex gap-2 rounded-xl border border-line bg-mist/50 p-3">
                <div class="grid flex-1 gap-2 {{ $parsed->count() > 2 ? 'sm:grid-cols-2' : '' }}">
                    @foreach($parsed as $f)
                        <div class="{{ $f['type'] === 'textarea' ? 'sm:col-span-2' : '' }}">
                            <label class="mb-0.5 block text-[0.7rem] font-semibold uppercase tracking-wide text-slate-500">{{ $f['label'] }}</label>
                            @if($f['type'] === 'textarea')
                                <textarea rows="2" class="a-input" :name="`{{ $name }}[${i}][{{ $f['key'] }}]`" x-model="row.{{ $f['key'] }}"></textarea>
                            @elseif($f['type'] === 'icon')
                                <select class="a-input" :name="`{{ $name }}[${i}][{{ $f['key'] }}]`" x-model="row.{{ $f['key'] }}">
                                    <option value="">—</option>
                                    @foreach(\App\Support\Icons::names() as $ic)<option value="{{ $ic }}">{{ $ic }}</option>@endforeach
                                </select>
                            @elseif($f['type'] === 'checkbox')
                                <input type="hidden" :name="`{{ $name }}[${i}][{{ $f['key'] }}]`" :value="row.{{ $f['key'] }} ? 1 : ''">
                                <input type="checkbox" class="a-check" x-model="row.{{ $f['key'] }}">
                            @else
                                <input type="text" class="a-input" :name="`{{ $name }}[${i}][{{ $f['key'] }}]`" x-model="row.{{ $f['key'] }}">
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="flex flex-col gap-1">
                    <button type="button" class="rounded p-1 text-slate-400 hover:bg-white hover:text-ink" @click="move(i, -1)" aria-label="Move up"><x-icon name="chevron-down" class="size-4 rotate-180" /></button>
                    <button type="button" class="rounded p-1 text-slate-400 hover:bg-white hover:text-ink" @click="move(i, 1)" aria-label="Move down"><x-icon name="chevron-down" class="size-4" /></button>
                    <button type="button" class="rounded p-1 text-slate-400 hover:bg-white hover:text-red-600" @click="remove(i)" aria-label="Remove"><x-icon name="x" class="size-4" /></button>
                </div>
            </div>
        </template>
        <p x-show="! rows.length" class="rounded-xl border border-dashed border-line p-4 text-center text-xs text-slate-500">Nothing added yet.</p>
    </div>
</div>
