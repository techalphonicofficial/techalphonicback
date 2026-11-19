@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $cls = match ($value) {
        'published', 'converted', 'active' => 'bg-emerald-50 text-emerald-700',
        'scheduled', 'contacted' => 'bg-sky-50 text-sky-700',
        'draft', 'qualified' => 'bg-amber-50 text-amber-700',
        'new' => 'bg-brand-50 text-brand-700',
        'lost', 'spam', 'archived' => 'bg-red-50 text-red-700',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp
<span {{ $attributes->merge(['class' => 'a-badge '.$cls]) }}><span class="a-dot"></span>{{ ucfirst($value) }}</span>
