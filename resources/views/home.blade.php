<x-app-layout>
    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            @if($isEmpty)
                <x-empty-state
                    title="Your catalog is empty"
                    description="Describe a scene or title in the AI Chat to start exploring."
                />
            @else
                {{-- Trending Row --}}
                @if(!empty($trending))
                    <x-title-row title="Trending Today">
                        @foreach($trending as $item)
                            <div class="w-[140px] sm:w-[160px] md:w-[180px] flex-none snap-start">
                                <x-poster-card
                                    :title="$item['title']"
                                    :year="$item['year']"
                                    :poster="$item['poster']"
                                    :url="$item['url']"
                                    :genres="$item['genres']"
                                    :mediaType="$item['media_type']"
                                    :title-id="$item['id']"
                                    :saved="in_array($item['id'], $savedTitleIds, true)"
                                    :data-tour="$loop->first ? 'poster-card' : null"
                                >
                                    <x-slot:watchlist>
                                        <button
                                            type="button"
                                            aria-label="Add to Watchlist"
                                            class="p-2 rounded-full bg-black/60 hover:bg-black/80 backdrop-blur-md text-white transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </x-slot:watchlist>
                                </x-poster-card>
                            </div>
                        @endforeach
                    </x-title-row>
                @endif

                {{-- Popular Row --}}
                @if($popular->isNotEmpty())
                    <x-title-row title="Popular">
                        @foreach($popular as $title)
                            <div class="w-[140px] sm:w-[160px] md:w-[180px] flex-none snap-start">
                                <x-poster-card
                                    :title="$title->title"
                                    :year="$title->release_year"
                                    :poster="$title->poster_path"
                                    :url="url('/titles/' . $title->slug)"
                                    :genres="$title->genres->pluck('name')->all()"
                                    :mediaType="$title->media_type"
                                    :title-id="$title->id"
                                    :saved="in_array($title->id, $savedTitleIds, true)"
                                    :data-tour="(empty($trending) && $loop->first) ? 'poster-card' : null"
                                >
                                    <x-slot:watchlist>
                                        <button
                                            type="button"
                                            aria-label="Add to Watchlist"
                                            class="p-2 rounded-full bg-black/60 hover:bg-black/80 backdrop-blur-md text-white transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </x-slot:watchlist>
                                </x-poster-card>
                            </div>
                        @endforeach
                    </x-title-row>
                @endif

                {{-- Top Rated Row --}}
                @if($topRated->isNotEmpty())
                    <x-title-row title="Top Rated">
                        @foreach($topRated as $title)
                            <div class="w-[140px] sm:w-[160px] md:w-[180px] flex-none snap-start">
                                <x-poster-card
                                    :title="$title->title"
                                    :year="$title->release_year"
                                    :poster="$title->poster_path"
                                    :url="url('/titles/' . $title->slug)"
                                    :genres="$title->genres->pluck('name')->all()"
                                    :mediaType="$title->media_type"
                                    :title-id="$title->id"
                                    :saved="in_array($title->id, $savedTitleIds, true)"
                                >
                                    <x-slot:watchlist>
                                        <button
                                            type="button"
                                            aria-label="Add to Watchlist"
                                            class="p-2 rounded-full bg-black/60 hover:bg-black/80 backdrop-blur-md text-white transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </x-slot:watchlist>
                                </x-poster-card>
                            </div>
                        @endforeach
                    </x-title-row>
                @endif

                {{-- Movies Row --}}
                @if($movies->isNotEmpty())
                    <x-title-row title="Movies" :viewAllUrl="url('/browse?type=movie')">
                        @foreach($movies as $title)
                            <div class="w-[140px] sm:w-[160px] md:w-[180px] flex-none snap-start">
                                <x-poster-card
                                    :title="$title->title"
                                    :year="$title->release_year"
                                    :poster="$title->poster_path"
                                    :url="url('/titles/' . $title->slug)"
                                    :genres="$title->genres->pluck('name')->all()"
                                    :mediaType="$title->media_type"
                                    :title-id="$title->id"
                                    :saved="in_array($title->id, $savedTitleIds, true)"
                                >
                                    <x-slot:watchlist>
                                        <button
                                            type="button"
                                            aria-label="Add to Watchlist"
                                            class="p-2 rounded-full bg-black/60 hover:bg-black/80 backdrop-blur-md text-white transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </x-slot:watchlist>
                                </x-poster-card>
                            </div>
                        @endforeach
                    </x-title-row>
                @endif

                {{-- TV Shows Row --}}
                @if($tvShows->isNotEmpty())
                    <x-title-row title="TV Shows" :viewAllUrl="url('/browse?type=tv')">
                        @foreach($tvShows as $title)
                            <div class="w-[140px] sm:w-[160px] md:w-[180px] flex-none snap-start">
                                <x-poster-card
                                    :title="$title->title"
                                    :year="$title->release_year"
                                    :poster="$title->poster_path"
                                    :url="url('/titles/' . $title->slug)"
                                    :genres="$title->genres->pluck('name')->all()"
                                    :mediaType="$title->media_type"
                                    :title-id="$title->id"
                                    :saved="in_array($title->id, $savedTitleIds, true)"
                                >
                                    <x-slot:watchlist>
                                        <button
                                            type="button"
                                            aria-label="Add to Watchlist"
                                            class="p-2 rounded-full bg-black/60 hover:bg-black/80 backdrop-blur-md text-white transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </x-slot:watchlist>
                                </x-poster-card>
                            </div>
                        @endforeach
                    </x-title-row>
                @endif
            @endif

            @if($showTourPrompt ?? false)
                <x-tour-prompt />
            @endif

            @if(!empty($tourSteps))
                <script id="guided-tour-steps" type="application/json">@json($tourSteps)</script>
                <x-tour-step :start-immediately="$startTour ?? false" />
            @endif
        </div>
    </div>
</x-app-layout>
