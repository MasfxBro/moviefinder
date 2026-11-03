@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => 'w-full min-h-[44px] px-3 py-2 bg-card border border-border text-text placeholder-muted rounded-xl text-[16px] sm:text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent disabled:opacity-50 disabled:cursor-not-allowed']) }}>
