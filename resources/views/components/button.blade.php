@props([
    'variant' => 'primary',
    'type' => 'button',
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center min-h-[44px] px-4 py-2 rounded-xl text-sm font-medium transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg disabled:opacity-50 disabled:cursor-not-allowed';

    $variantClasses = match($variant) {
        'primary' => 'bg-accent text-text hover:opacity-90',
        'secondary' => 'bg-card text-text border border-border hover:bg-elevated',
        'ghost' => 'bg-transparent text-muted hover:text-text hover:bg-surface',
        'danger' => 'bg-danger text-text hover:opacity-90',
        default => 'bg-accent text-text hover:opacity-90',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => $baseClasses . ' ' . $variantClasses]) }}
>
    {{ $slot }}
</button>
