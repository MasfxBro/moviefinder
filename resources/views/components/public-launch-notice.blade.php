@props([
    'feature' => null,
    'message' => null,
    'variant' => 'card',
])

@php
    $text = $message ?? ($feature ? "{$feature} will be available at public launch." : 'Available at public launch.');
@endphp

@if ($variant === 'badge' || $variant === 'label')
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-accent/15 text-accent border border-accent/20']) }}>
        {{ $text }}
    </span>
@else
    <div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-6 text-center bg-card border border-accent/30 rounded-2xl scene-frame my-4']) }}>
        <div class="scene-frame-bracket scene-frame-tl"></div>
        <div class="scene-frame-bracket scene-frame-tr"></div>
        <div class="scene-frame-bracket scene-frame-bl"></div>
        <div class="scene-frame-bracket scene-frame-br"></div>

        <div class="mb-3 text-accent">
            <svg class="w-10 h-10 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        </div>

        @if ($feature)
            <h3 class="text-base font-semibold text-text mb-1">
                {{ $feature }}
            </h3>
        @endif

        <p class="text-sm text-muted max-w-md">
            {{ $text }}
        </p>
    </div>
@endif
