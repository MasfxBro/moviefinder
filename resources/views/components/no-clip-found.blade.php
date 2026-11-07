@props([
    'title' => '',
    'query' => '',
    'watchUrl' => '#where-to-watch',
])

@php
    $searchTerm = trim($query ?: ($title ? $title.' scene' : 'movie clip'));
    $youtubeSearchUrl = 'https://www.youtube.com/results?search_query='.urlencode($searchTerm);
@endphp

<div {{ $attributes->merge(['class' => 'scene-frame relative bg-surface border border-border rounded-xl p-4 sm:p-5 flex flex-col gap-3']) }}>
    <div class="scene-frame-bracket scene-frame-tl"></div>
    <div class="scene-frame-bracket scene-frame-tr"></div>
    <div class="scene-frame-bracket scene-frame-bl"></div>
    <div class="scene-frame-bracket scene-frame-br"></div>

    <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-lg bg-surface border border-border flex items-center justify-center shrink-0 text-muted">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
            </svg>
        </div>

        <div class="space-y-1">
            <h4 class="text-sm font-semibold text-text">
                No clip found
            </h4>
            <p class="text-xs text-muted leading-relaxed">
                We could not find an official or trusted preview clip for this scene.
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-border/50">
        {{-- Search on YouTube link --}}
        <a
            href="{{ $youtubeSearchUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center min-h-[44px] px-3.5 py-2 rounded-xl text-xs sm:text-sm font-medium bg-surface border border-border text-text hover:border-accent hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent"
        >
            <span>Search on YouTube</span>
            <svg class="w-3.5 h-3.5 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>

        {{-- Where to watch link --}}
        <a
            href="{{ $watchUrl }}"
            class="inline-flex items-center justify-center min-h-[44px] px-3.5 py-2 rounded-xl text-xs sm:text-sm font-medium text-accent hover:underline focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
        >
            <span>Where to watch</span>
            <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>
</div>
