@props([
    'disabled' => false,
])

<input
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge([
        'class' => 'w-full min-h-[44px] px-3 py-2 bg-card border border-border rounded-xl text-text placeholder-muted text-[16px] sm:text-sm transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent disabled:opacity-50 disabled:cursor-not-allowed'
    ]) }}
/>
