@props([
    'placeholder' => 'Search titles',
    'inputRef' => 'input',
])

<div x-data="liveSearch()" x-on:keydown.window="shortcut($event)" class="relative inline-block w-full max-w-md">
    <input
        type="search"
        x-ref="{{ $inputRef }}"
        x-model="query"
        x-on:keydown="keydown($event)"
        x-on:focus="open = true; if (recent.length === 0) fetchRecent()"
        x-on:blur="setTimeout(() => open = false, 200)"
        placeholder="{{ $placeholder }}"
        class="w-full min-h-[44px] px-3 py-2 bg-card border border-border rounded-xl text-text placeholder-muted text-[16px] sm:text-sm transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent"
        aria-label="Search titles"
    />

    <div
        x-show="open && (query.trim().length >= 2 || recent.length > 0)"
        x-transition:enter="ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute z-50 mt-1 w-full bg-elevated border border-border rounded-xl shadow-lg overflow-hidden"
    >
        {{-- Recent searches section --}}
        <div x-show="query.trim().length < 2 && recent.length > 0" class="p-2">
            <div class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-muted">
                Recent searches
            </div>
            <template x-for="(item, index) in recent" :key="index">
                <button
                    type="button"
                    x-on:click="selectRecent(item)"
                    :class="[
                        'w-full text-left px-3 py-2 text-sm rounded-lg flex items-center justify-between group transition-colors',
                        focused === index ? 'bg-accent text-text' : 'text-text hover:bg-surface',
                    ]"
                >
                    <span class="truncate" x-text="item"></span>
                    <svg class="w-4 h-4 text-muted group-hover:text-accent shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </template>
        </div>

        {{-- Live search results section --}}
        <div x-show="query.trim().length >= 2">
            <div x-show="loading" class="p-3 text-center text-sm text-muted">
                Searching…
            </div>

            <div x-show="error && ! loading" class="p-3 text-center text-sm text-danger">
                The server could not be reached. Please check your connection and try again.
                <button
                    type="button"
                    x-on:click="search(query)"
                    class="ml-2 text-sm text-accent hover:underline"
                >
                    Retry
                </button>
            </div>

            <template x-for="(result, index) in results" :key="index">
                <div
                    x-show="! loading && ! error"
                    x-on:click="select(result)"
                    :class="[
                        'px-3 py-2 text-sm cursor-pointer border-b border-border last:border-b-0',
                        focused === index ? 'bg-accent text-text' : 'bg-elevated text-text hover:bg-surface',
                    ]"
                >
                    <div class="font-medium" x-html="highlight(query, result.title)"></div>
                    <div class="text-xs text-muted">
                        <span x-text="result.media_type"></span>
                        <template x-if="result.release_year">
                            <span class="mx-1">·</span>
                            <span x-text="result.release_year"></span>
                        </template>
                    </div>
                    <template x-if="result.not_in_catalog">
                        <div class="text-xs text-warning mt-1">Not in our catalog yet — opening it adds it</div>
                    </template>
                </div>
            </template>

            <div x-show="! loading && ! error && results.length === 0" class="p-3 text-center text-sm text-muted">
                No results
            </div>
        </div>
    </div>
</div>
