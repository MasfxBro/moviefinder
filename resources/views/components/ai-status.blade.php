@props([
    'message' => null,
])

<div {{ $attributes->merge(['role' => 'status', 'class' => 'rounded-xl border border-border bg-card/70 px-3.5 py-2.5 text-xs sm:text-sm text-muted flex items-start gap-2.5 leading-relaxed']) }}>
    <svg class="h-4 w-4 shrink-0 text-muted mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="16" x2="12" y2="12" />
        <line x1="12" y1="8" x2="12.01" y2="8" />
    </svg>
    <div class="flex-1">
        {{ $message ?? $slot }}
    </div>
</div>
