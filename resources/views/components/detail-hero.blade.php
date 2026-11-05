@props([
    'title',
])

@php
    $imageBase = config('services.tmdb.image_base', 'https://image.tmdb.org/t/p/');
    $backdropUrl = $title->backdrop_path ? $imageBase . 'w1280' . $title->backdrop_path : null;
    $posterUrl = $title->poster_path ? $imageBase . 'w500' . $title->poster_path : null;
@endphp

<div class="relative rounded-2xl sm:rounded-3xl overflow-hidden bg-card border border-border shadow-lg">
    {{-- Backdrop Image & Gradient Overlay --}}
    @if ($backdropUrl)
        <div class="absolute inset-0 z-0">
            <img
                src="{{ $backdropUrl }}"
                alt=""
                aria-hidden="true"
                class="w-full h-full object-cover opacity-25 filter blur-xs scale-105"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-card via-card/80 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-card via-card/60 to-transparent"></div>
        </div>
    @endif

    {{-- Hero Content --}}
    <div class="relative z-10 p-5 sm:p-8 lg:p-10 flex flex-col md:flex-row gap-6 lg:gap-8 items-start">
        {{-- Poster --}}
        @if ($posterUrl)
            <div class="w-36 sm:w-48 md:w-56 lg:w-64 flex-shrink-0 mx-auto md:mx-0">
                <div class="aspect-[2/3] rounded-xl overflow-hidden bg-surface border border-border shadow-md">
                    <img
                        src="{{ $posterUrl }}"
                        alt="Poster of {{ $title->title }}"
                        class="w-full h-full object-cover"
                    />
                </div>
            </div>
        @endif

        {{-- Details & Actions --}}
        <div class="flex-1 min-w-0 flex flex-col gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-text leading-tight">
                    {{ $title->title }}
                </h1>

                @if (filled($title->original_title) && $title->original_title !== $title->title)
                    <p class="text-xs sm:text-sm text-faint mt-1">
                        Original title: {{ $title->original_title }}
                    </p>
                @endif

                @if (filled($title->tagline))
                    <p class="text-sm sm:text-base italic text-muted mt-2">
                        &ldquo;{{ $title->tagline }}&rdquo;
                    </p>
                @endif
            </div>

            {{-- Meta Chips --}}
            <x-meta-chips :title="$title" />

            {{-- Action Buttons (Desktop / Tablet) --}}
            <div class="hidden sm:flex flex-wrap items-center gap-3 pt-2">
                {{-- Watchlist slot --}}
                @if (isset($watchlist))
                    {{ $watchlist }}
                @else
                    <button
                        type="button"
                        class="inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-xl text-sm font-medium bg-accent text-text hover:opacity-90 transition-colors focus:outline-none focus:ring-2 focus:ring-accent"
                        aria-label="Add to watchlist"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add to watchlist
                    </button>
                @endif

                {{-- Trailer quick anchor / button slot --}}
                @if (filled($title->trailer_youtube_key))
                    <a
                        href="#trailer"
                        class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 rounded-xl text-sm font-medium bg-surface text-text border border-border hover:bg-elevated transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2 text-danger" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z" />
                        </svg>
                        Trailer
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
