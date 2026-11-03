@props(['plan'])

@php
    $classes = match($plan) {
        'PLUS' => 'bg-accent-soft text-accent',
        'PRO' => 'bg-pro text-bg',
        default => 'bg-muted text-bg',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase ' . $classes]) }}>
    {{ $plan }}
</span>
