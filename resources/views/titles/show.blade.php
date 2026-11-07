<x-app-layout>
    <div class="py-6 sm:py-8 space-y-8">
        {{-- Detail Hero --}}
        <x-detail-hero :title="$title">
            <x-slot:watchlist>
                <div id="watchlist-slot">
                    @if (auth()->user()?->hasVerifiedEmail())
                        <x-watchlist-toggle :title-id="$title->id" :saved="$watchlistSaved" />
                    @else
                        <p class="text-sm text-muted">Verify your email to use the watchlist.</p>
                    @endif
                </div>
            </x-slot:watchlist>
        </x-detail-hero>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            {{-- Left 2 Columns: Synopsis, Cast, Trailer slot, Where to watch slot --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Expandable Synopsis --}}
                @if (filled($title->overview))
                    <section class="bg-card border border-border rounded-2xl p-5 sm:p-6" aria-labelledby="synopsis-heading">
                        <h2 id="synopsis-heading" class="text-lg font-bold text-text mb-3">
                            Synopsis
                        </h2>

                        <div x-data="{ expanded: false }">
                            <p
                                class="text-sm sm:text-base text-muted leading-relaxed"
                                :class="expanded ? '' : 'line-clamp-4'"
                            >
                                {{ $title->overview }}
                            </p>

                            @if (strlen($title->overview) > 240)
                                <button
                                    type="button"
                                    x-on:click="expanded = ! expanded"
                                    class="mt-3 inline-flex items-center text-sm font-medium text-accent hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded"
                                >
                                    <span x-text="expanded ? 'Show less' : 'Read more'">Read more</span>
                                    <svg
                                        class="w-4 h-4 ml-1 transition-transform duration-150"
                                        :class="expanded ? 'rotate-180' : ''"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </section>
                @endif

                {{-- Cast & Crew --}}
                @if ($title->people->isNotEmpty())
                    <section class="bg-card border border-border rounded-2xl p-5 sm:p-6" aria-labelledby="people-heading">
                        <h2 id="people-heading" class="text-lg font-bold text-text mb-4">
                            Cast and Crew
                        </h2>
                        <x-cast-list :people="$title->people" />
                    </section>
                @endif

                {{-- Trailer Slot (T-15) --}}
                <section id="trailer" class="scroll-mt-20 bg-card border border-border rounded-2xl p-5 sm:p-6" aria-labelledby="trailer-heading">
                    <h2 id="trailer-heading" class="text-lg font-bold text-text mb-3">
                        Trailer
                    </h2>
                    <div id="trailer-slot">
                        <x-trailer-embed
                            :youtube-key="$title->trailer_youtube_key"
                            :title="$title->title"
                            :backdrop-path="$title->backdrop_path"
                        />
                    </div>
                </section>

                {{-- Where to watch Slot (T-15) --}}
                <section id="where-to-watch" class="scroll-mt-20 bg-card border border-border rounded-2xl p-5 sm:p-6" aria-labelledby="watch-heading">
                    <h2 id="watch-heading" class="text-lg font-bold text-text mb-3">
                        Where to watch
                    </h2>
                    <div id="where-to-watch-slot">
                        <x-where-to-watch :providers="$watchProviders" />
                    </div>
                </section>
            </div>

            {{-- Right 1 Column: Title Chat Card (Reserved for F-08) & Report link --}}
            <div class="space-y-6">
                {{-- Embedded title chat --}}
                <section id="title-chat-slot" class="scroll-mt-20 min-w-0 overflow-hidden bg-card border border-border rounded-2xl" aria-labelledby="chat-heading">
                    <h2 id="chat-heading" class="sr-only">Ask about this title</h2>
                    <x-ai-chat variant="embedded" :title="$title->title" :title-id="$title->id" />
                </section>

                {{-- Report a problem link --}}
                <div class="px-2 text-center sm:text-left">
                    <a
                        href="{{ Route::has('feedback') ? route('feedback', ['title_id' => $title->id]) : url('/feedback?title_id='.$title->id) }}"
                        class="text-xs text-faint hover:text-muted transition-colors inline-flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Report a problem with this title
                    </a>
                </div>
            </div>
        </div>

        {{-- Mobile Sticky Action Bar --}}
        <div class="sm:hidden fixed bottom-0 left-0 right-0 z-30 p-3 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] bg-bg/95 backdrop-blur border-t border-border shadow-lg">
            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasVerifiedEmail())
                    <div class="flex-1"><x-watchlist-toggle :title-id="$title->id" :saved="$watchlistSaved" /></div>
                @endif

                @if (filled($title->trailer_youtube_key))
                    <a
                        href="#trailer"
                        class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 rounded-xl text-sm font-medium bg-card text-text border border-border hover:bg-elevated transition-colors"
                        aria-label="View trailer"
                    >
                        <svg class="w-4 h-4 text-danger" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z" />
                        </svg>
                    </a>
                @endif

                <a
                    href="#title-chat-slot"
                    class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 rounded-xl text-sm font-medium bg-card text-text border border-border hover:bg-elevated transition-colors"
                    aria-label="Ask AI about title"
                >
                    <div class="w-4 h-4 rounded bg-accent-soft text-accent flex items-center justify-center font-bold text-[10px]">
                        AI
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
