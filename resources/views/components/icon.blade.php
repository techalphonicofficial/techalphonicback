@props(['name' => 'sparkles', 'label' => null])
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false"
     @if($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
     {{ $attributes->merge(['class' => $attributes->has('class') ? '' : 'size-5']) }}>{!! \App\Support\Icons::svg($name) !!}</svg>
