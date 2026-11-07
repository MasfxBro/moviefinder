@props([
    'variant' => 'default',
    'title' => 'Keep searching without waiting',
    'text' => null,
])

@php
$bodyText = $text ?? match ($variant) {
    'clip_teaser' => 'Looking for a specific clip? Plus and Pro can search the web for matching clips.',
    'plus_to_pro' => 'Pro checks each suggestion against your clues and explains what matches and what does not.',
    default => 'Plus and Pro use separate AI capacity, so you can keep searching at any time.',
};
@endphp

<div
    x-data="{ dismissed: false }"
    x-show="!dismissed"
    {{ $attributes->merge(['class' => 'bg-accent-soft border border-accent/25 rounded-2xl p-4 flex flex-col gap-3']) }}
>
    <div>
        <h4 class="text-sm font-semibold text-accent">
            {{ $title }}
        </h4>
        <p class="text-xs sm:text-sm text-text/90 mt-1 leading-relaxed">
            {{ $bodyText }}
        </p>
    </div>

    <div class="flex items-center gap-3 pt-1">
        <a
            href="{{ route('pricing') }}"
            class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-accent text-white text-xs sm:text-sm font-medium hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent transition-opacity min-h-[38px]"
        >
            {{ __('See plans') }}
        </a>
        <button
            type="button"
            x-on:click="dismissed = true"
            class="text-xs sm:text-sm text-muted hover:text-text transition-colors px-2 py-1 min-h-[38px] inline-flex items-center"
        >
            {{ __('Maybe later') }}
        </button>
    </div>
</div>
