@props([
    'title' => 'No items found',
    'description' => 'There are no items to display at this time.',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-8 text-center bg-card border border-border rounded-2xl scene-frame']) }}>
    <div class="scene-frame-bracket scene-frame-tl"></div>
    <div class="scene-frame-bracket scene-frame-tr"></div>
    <div class="scene-frame-bracket scene-frame-bl"></div>
    <div class="scene-frame-bracket scene-frame-br"></div>

    <div class="mb-4 text-muted">
        @if (isset($icon))
            {{ $icon }}
        @else
            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M4 20h16C20.5523 20 21 19.5523 21 19V5C21 4.44772 20.5523 4 20 4H4C3.44772 4 3 4.44772 3 5V19C3 19.5523 3.44772 20 4 20Z" />
            </svg>
        @endif
    </div>

    <h3 class="text-lg font-semibold text-text mb-1">
        {{ $title }}
    </h3>

    <p class="text-sm text-muted max-w-sm mb-6">
        {{ $description }}
    </p>

    @if (isset($action))
        <div>
            {{ $action }}
        </div>
    @endif
</div>
