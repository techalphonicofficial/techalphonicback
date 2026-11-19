@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'help' => null,
    'options' => [],
    'placeholder' => null,
    'required' => false,
    'rows' => 4,
    'editor' => false,
    'counter' => null, // [min, max]
    'empty' => '— None —',
])
@php
    $dot = str_replace(['[', ']'], ['.', ''], $name);
    $id = 'f_'.str_replace(['.', '[', ']'], '_', $name);
    $val = old($dot, $value);
@endphp
<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if($label && $type !== 'checkbox')
        <label for="{{ $id }}" class="a-label">{{ $label }} @if($required)<span class="text-brand">*</span>@endif</label>
    @endif

    @switch($type)
        @case('textarea')
            @if($counter)
                <div x-data="counter({{ $counter[0] }}, {{ $counter[1] }}, @js((string) $val))">
                    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="a-input" placeholder="{{ $placeholder }}" x-model="text" @required($required)>{{ $val }}</textarea>
                    <p class="a-help flex justify-between"><span>{{ $help }}</span><span :class="tone"><span x-text="len"></span> / {{ $counter[0] }}–{{ $counter[1] }} recommended</span></p>
                </div>
            @else
                <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="a-input" placeholder="{{ $placeholder }}" @if($editor) data-editor data-height="{{ $rows * 40 }}" @endif @required($required)>{{ $val }}</textarea>
            @endif
            @break

        @case('select')
            <select id="{{ $id }}" name="{{ $name }}" class="a-input" @required($required) {{ $attributes->except('class') }}>
                @if($empty !== false)<option value="">{{ $empty }}</option>@endif
                @foreach($options as $k => $v)
                    <option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $v }}</option>
                @endforeach
            </select>
            @break

        @case('multiselect')
            <select id="{{ $id }}" name="{{ $name }}[]" multiple size="{{ min(10, max(4, count($options))) }}" class="a-input">
                @foreach($options as $k => $v)
                    <option value="{{ $k }}" @selected(in_array((string) $k, array_map('strval', (array) $val), true))>{{ $v }}</option>
                @endforeach
            </select>
            @break

        @case('checkbox')
            <label class="inline-flex cursor-pointer items-center gap-2.5">
                <input type="hidden" name="{{ $name }}" value="0">
                <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" class="a-check" @checked((bool) $val) {{ $attributes->except('class') }}>
                <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
            </label>
            @break

        @case('color')
            <div class="flex items-center gap-2">
                <input type="color" value="{{ $val ?: '#FE5C36' }}" class="h-10 w-12 cursor-pointer rounded-lg border border-line" oninput="this.nextElementSibling.value = this.value.toUpperCase()">
                <input id="{{ $id }}" type="text" name="{{ $name }}" value="{{ $val }}" class="a-input font-mono" pattern="#[0-9A-Fa-f]{6}">
            </div>
            @break

        @default
            @if($counter)
                <div x-data="counter({{ $counter[0] }}, {{ $counter[1] }}, @js((string) $val))">
                    <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" class="a-input" placeholder="{{ $placeholder }}" x-model="text" @required($required) {{ $attributes->except('class') }}>
                    <p class="a-help flex justify-between"><span>{{ $help }}</span><span :class="tone"><span x-text="len"></span> / {{ $counter[0] }}–{{ $counter[1] }} recommended</span></p>
                </div>
            @else
                <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $val }}" class="a-input" placeholder="{{ $placeholder }}" @required($required) {{ $attributes->except('class') }}>
            @endif
    @endswitch

    @if($help && ! $counter && $type !== 'checkbox')<p class="a-help">{{ $help }}</p>@endif
    @if($help && $type === 'checkbox')<p class="a-help ml-6">{{ $help }}</p>@endif
    @error($dot)<p class="a-error">{{ $message }}</p>@enderror
</div>
