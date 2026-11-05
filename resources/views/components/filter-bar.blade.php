@props([
    'action',
    'filters' => [],
    'genres' => null,
    'chips' => [],
])

@php
    $genres = $genres ?? collect();
    $selectedGenres = array_map('intval', (array) ($filters['genres'] ?? []));
    $selectClasses = 'w-full min-h-[44px] px-3 py-2 bg-card border border-border rounded-xl text-text text-[16px] sm:text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent';
    $labelClasses = 'block text-xs font-medium text-muted mb-1';
@endphp

<div x-data="{ open: false }" @keydown.escape.window="open = false" class="space-y-4">
    {{-- Active chips and mobile trigger --}}
    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            @click="open = true"
            class="md:hidden inline-flex items-center justify-center min-h-[44px] px-4 rounded-xl bg-card border border-border text-sm font-medium text-text hover:bg-elevated transition-colors"
            aria-haspopup="dialog"
            :aria-expanded="open"
        >
            Filters
        </button>

        @foreach ($chips as $chip)
            <a
                href="{{ $chip['url'] }}"
                class="inline-flex items-center gap-1.5 min-h-[44px] px-3 rounded-xl bg-accent-soft text-accent text-sm font-medium hover:opacity-90 transition-opacity"
            >
                {{ $chip['label'] }}
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="sr-only">Remove filter</span>
            </a>
        @endforeach

        @if ($chips !== [])
            <a href="{{ $action }}" class="text-sm text-muted hover:text-text underline underline-offset-4 min-h-[44px] inline-flex items-center">
                Clear all
            </a>
        @endif
    </div>

    {{-- Mobile backdrop --}}
    <div
        x-show="open"
        x-cloak
        @click="open = false"
        class="fixed inset-0 z-40 bg-bg/80 md:hidden"
        aria-hidden="true"
    ></div>

    {{-- Filter form: inline panel on desktop, bottom sheet on mobile --}}
    <form
        method="GET"
        action="{{ $action }}"
        role="search"
        aria-label="Filter titles"
        class="max-md:hidden rounded-2xl border border-border bg-surface p-4 sm:p-6"
        :class="open && 'max-md:!block max-md:fixed max-md:inset-x-0 max-md:bottom-0 max-md:z-50 max-md:max-h-[85vh] max-md:overflow-y-auto max-md:rounded-t-3xl max-md:border-t max-md:p-4 max-md:pb-[calc(1rem+env(safe-area-inset-bottom))]'"
    >
        <div class="flex items-center justify-between mb-4 md:hidden">
            <h2 class="text-base font-semibold text-text">Filters</h2>
            <button
                type="button"
                @click="open = false"
                class="inline-flex items-center justify-center w-11 h-11 rounded-xl text-muted hover:text-text hover:bg-elevated transition-colors"
                aria-label="Close filters"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="filter-type" class="{{ $labelClasses }}">Type</label>
                <select id="filter-type" name="type" class="{{ $selectClasses }}">
                    <option value="">All types</option>
                    <option value="movie" @selected(($filters['type'] ?? null) === 'movie')>Movies</option>
                    <option value="tv" @selected(($filters['type'] ?? null) === 'tv')>TV Shows</option>
                </select>
                @error('type')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="filter-sort" class="{{ $labelClasses }}">Sort</label>
                <select id="filter-sort" name="sort" class="{{ $selectClasses }}">
                    <option value="popularity" @selected(($filters['sort'] ?? 'popularity') === 'popularity')>Popularity</option>
                    <option value="rating" @selected(($filters['sort'] ?? null) === 'rating')>Rating</option>
                    <option value="newest" @selected(($filters['sort'] ?? null) === 'newest')>Newest</option>
                </select>
                @error('sort')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <span class="{{ $labelClasses }}">Year range</span>
                <div class="flex items-center gap-2">
                    <label for="filter-year-from" class="sr-only">Year from</label>
                    <input
                        id="filter-year-from"
                        name="year_from"
                        type="number"
                        inputmode="numeric"
                        min="1900"
                        max="{{ (int) date('Y') + 1 }}"
                        placeholder="From"
                        value="{{ $filters['year_from'] ?? '' }}"
                        class="w-full min-h-[44px] px-3 py-2 bg-card border border-border rounded-xl text-text placeholder-muted text-[16px] sm:text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent"
                    >
                    <label for="filter-year-to" class="sr-only">Year to</label>
                    <input
                        id="filter-year-to"
                        name="year_to"
                        type="number"
                        inputmode="numeric"
                        min="1900"
                        max="{{ (int) date('Y') + 1 }}"
                        placeholder="To"
                        value="{{ $filters['year_to'] ?? '' }}"
                        class="w-full min-h-[44px] px-3 py-2 bg-card border border-border rounded-xl text-text placeholder-muted text-[16px] sm:text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent"
                    >
                </div>
                @error('year_from')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
                @error('year_to')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="filter-min-rating" class="{{ $labelClasses }}">Minimum rating</label>
                <select id="filter-min-rating" name="min_rating" class="{{ $selectClasses }}">
                    <option value="">Any rating</option>
                    @foreach ([5, 6, 7, 8, 9] as $rating)
                        <option value="{{ $rating }}" @selected((string) ($filters['min_rating'] ?? '') === (string) $rating)>{{ $rating }}+</option>
                    @endforeach
                </select>
                @error('min_rating')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="filter-director" class="{{ $labelClasses }}">Director</label>
                <input
                    id="filter-director"
                    name="director"
                    type="text"
                    minlength="3"
                    maxlength="150"
                    placeholder="At least 3 characters"
                    value="{{ $filters['director'] ?? '' }}"
                    class="w-full min-h-[44px] px-3 py-2 bg-card border border-border rounded-xl text-text placeholder-muted text-[16px] sm:text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent"
                >
                @error('director')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <fieldset class="mt-4">
            <legend class="{{ $labelClasses }}">Genres</legend>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-4 max-h-48 overflow-y-auto pr-1">
                @foreach ($genres as $genre)
                    <label class="flex items-center gap-2 min-h-[44px] text-sm text-text cursor-pointer">
                        <input
                            type="checkbox"
                            name="genres[]"
                            value="{{ $genre->id }}"
                            @checked(in_array((int) $genre->id, $selectedGenres, true))
                            class="rounded border-border bg-card text-accent focus:ring-accent"
                        >
                        <span class="truncate">{{ $genre->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('genres')
                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
            @enderror
            @error('genres.*')
                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
            @enderror
        </fieldset>

        <div class="mt-6 flex items-center gap-3">
            <x-button type="submit" variant="primary">Apply filters</x-button>
            <a href="{{ $action }}" class="text-sm text-muted hover:text-text underline underline-offset-4">Reset</a>
        </div>
    </form>
</div>
