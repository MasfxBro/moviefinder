@props([
    'youtubeKey' => null,
    'title' => '',
    'backdropPath' => null,
])

@if (filled($youtubeKey))
    @php
        $embedUrl = "https://www.youtube-nocookie.com/embed/{$youtubeKey}?rel=0";
        $cleanTitle = addslashes(str_replace(["'", '"'], '', (string) $title));
        $iframeTitle = $cleanTitle ? "{$cleanTitle} Trailer" : 'Trailer';
    @endphp

    <div
        x-data="{
            loaded: false,
            loadTrailer() {
                if (this.loaded) {
                    return;
                }
                this.loaded = true;
                this.$nextTick(() => {
                    const container = this.$refs.playerContainer;
                    if (container && ! container.querySelector('iframe')) {
                        const frame = document.createElement('iframe');
                        frame.setAttribute('src', '{{ $embedUrl }}');
                        frame.setAttribute('title', '{{ $iframeTitle }}');
                        frame.setAttribute('class', 'w-full h-full border-0');
                        frame.setAttribute('allow', 'accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
                        frame.setAttribute('allowfullscreen', 'true');
                        container.appendChild(frame);
                    }
                });
            }
        }"
        class="w-full"
    >
        <div
            x-show="loaded"
            x-cloak
            x-ref="playerContainer"
            class="aspect-video w-full rounded-xl overflow-hidden bg-black"
        ></div>

        <button
            type="button"
            x-show="! loaded"
            x-on:click="loadTrailer()"
            class="aspect-video w-full rounded-xl bg-surface border border-border flex flex-col items-center justify-center p-6 text-center relative overflow-hidden group cursor-pointer focus:outline-none focus:ring-2 focus:ring-accent"
            aria-label="Play trailer"
        >
            @if ($backdropPath)
                <img
                    src="https://image.tmdb.org/t/p/w780{{ $backdropPath }}"
                    alt=""
                    class="absolute inset-0 w-full h-full object-cover opacity-20 filter blur-[1px] group-hover:scale-105 transition-transform duration-300 pointer-events-none"
                    loading="lazy"
                />
            @endif

            <div class="relative z-10 flex flex-col items-center pointer-events-none">
                <div class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-accent text-text group-hover:scale-105 transition-transform mb-3 shadow-lg">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8 ml-1 fill-current" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                </div>
                <span class="text-sm font-semibold text-text mb-1">Watch Trailer</span>
                <p class="text-xs text-faint">Playing loads content from YouTube</p>
            </div>
        </button>
    </div>
@else
    <div class="rounded-xl bg-surface border border-border p-6 text-center text-sm text-faint flex flex-col items-center justify-center gap-3">
        <span>No trailer available for this title</span>
        @if (filled($title))
            <a
                href="https://www.youtube.com/results?search_query={{ urlencode($title.' trailer') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center text-xs font-medium text-accent hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded"
            >
                Search trailer on YouTube
                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        @endif
    </div>
@endif
