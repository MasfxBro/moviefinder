@props(['titleId', 'saved' => false, 'compact' => false])

<div x-data="watchlistToggle({ url: $el.dataset.url, saved: @js((bool) $saved) })" data-url="{{ route('watchlist.toggle', ['title' => $titleId]) }}">
    <button
        type="button"
        x-on:click="toggle()"
        :aria-pressed="saved.toString()"
        :aria-label="saved ? 'Remove from watchlist' : 'Add to watchlist'"
        :disabled="busy"
        class="inline-flex items-center justify-center min-h-[44px] rounded-xl text-sm font-medium bg-accent text-text hover:opacity-90 disabled:opacity-60 transition-colors focus:outline-none focus:ring-2 focus:ring-accent {{ $compact ? 'p-2 bg-black/60 hover:bg-black/80 backdrop-blur-md text-white rounded-full' : 'px-5 py-2.5' }}"
    >
        <svg class="w-4 h-4 {{ $compact ? '' : 'mr-2' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path x-show="!saved" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            <path x-show="saved" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l4 4L19 6" />
        </svg>
        @unless($compact)
            <span x-text="saved ? 'Remove from watchlist' : 'Add to watchlist'">{{ $saved ? 'Remove from watchlist' : 'Add to watchlist' }}</span>
        @endunless
    </button>
    <div x-cloak x-show="toast" x-transition role="status" class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 z-50 flex items-center justify-between gap-4 max-w-lg p-4 rounded-xl border border-border bg-card text-text shadow-lg">
        <span class="text-sm" x-text="toast"></span>
        <button x-show="previous !== undefined" type="button" x-bind:disabled="busy" x-on:click="undo()" class="min-h-[44px] px-3 text-sm font-semibold text-accent hover:underline">Undo</button>
        <button type="button" x-on:click="toast = ''; previous = undefined" aria-label="Close notification" class="min-h-[44px] px-2 text-muted hover:text-text">×</button>
    </div>
</div>
