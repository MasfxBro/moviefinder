@props([
    'placeholder' => 'Search titles',
])

<div x-data="liveSearch()" class="relative inline-block w-full max-w-md">
    <input
        type="search"
        ref="input"
        x-model="query"
        x-on:keydown="keydown($event)"
        x-on:focus="open = true"
        x-on:blur="setTimeout(() => open = false, 150)"
        placeholder="{{ $placeholder }}"
        class="w-full min-h-[44px] px-3 py-2 bg-card border border-border rounded-xl text-text placeholder-muted text-[16px] sm:text-sm transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent"
        aria-label="Search titles"
    />

    <div
        x-show="open && (results.length > 0 || loading || error)"
        x-transition:enter="ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute z-50 mt-1 w-full bg-elevated border border-border rounded-xl shadow-lg"
    >
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
                    focused === index ? 'bg-accent text-text' : 'bg-elevated text-text',
                ]"
            >
                <div class="font-medium" x-html="highlight(query, result.title)"></div>
                <div class="text-xs text-muted">
                    {{-- media type / year --}}
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
