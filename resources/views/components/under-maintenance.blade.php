@props([
    'feature' => 'This feature',
    'title' => null,
    'description' => null,
])

@php
    $verb = str_ends_with(strtolower(trim($feature)), 's') ? 'are' : 'is';
    $featureTitle = $title ?? "{$feature} {$verb} under maintenance";
    $featureDesc = $description ?? 'We are performing scheduled maintenance or improvements. Please check back shortly.';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-8 text-center bg-card border border-border rounded-2xl scene-frame my-6']) }}>
    <div class="scene-frame-bracket scene-frame-tl"></div>
    <div class="scene-frame-bracket scene-frame-tr"></div>
    <div class="scene-frame-bracket scene-frame-bl"></div>
    <div class="scene-frame-bracket scene-frame-br"></div>

    <div class="mb-4 text-warning">
        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
    </div>

    <h3 class="text-lg font-semibold text-text mb-1">
        {{ $featureTitle }}
    </h3>

    <p class="text-sm text-muted max-w-md">
        {{ $featureDesc }}
    </p>
</div>
