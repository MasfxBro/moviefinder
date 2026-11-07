@props([
    'unitsLeft' => null,
    'unitsLimit' => null,
    'smartLeft' => null,
    'plan' => 'free',
])

<div {{ $attributes->merge(['class' => 'px-4 py-2.5 border-t border-border bg-surface/90 flex flex-col gap-1 text-xs text-muted select-none']) }}>
    <div class="flex items-center justify-between gap-2">
        <span x-text="quotaCounterText">
            @if ($unitsLeft !== null && $unitsLimit !== null)
                {{ $unitsLeft }} of {{ $unitsLimit }} AI searches left today
            @endif
        </span>
        <template x-if="isPro && quota && quota.smart_left !== undefined">
            <span class="text-pro font-medium" x-text="smartCounterText">
                @if ($smartLeft !== null)
                    {{ $smartLeft }} Smart searches left today
                @endif
            </span>
        </template>
    </div>
</div>
