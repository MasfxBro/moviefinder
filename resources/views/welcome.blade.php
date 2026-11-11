<x-app-layout>
    {{-- Hero --}}
    <x-landing-hero />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-32 pb-32">
        {{-- How it works --}}
        <section>
            <h2 class="text-3xl font-bold text-center mb-16">How it works</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <x-step-card 
                    step="1" 
                    title="Describe a scene" 
                    description="Type a few words about what happens. 'A man wakes up on a beach with a spinning top' or 'A dinner party where no one can leave'." 
                />
                <x-step-card 
                    step="2" 
                    title="Get verified results" 
                    description="Our AI scans thousands of titles to find the exact match, providing context on why it fits your description." 
                />
                <x-step-card 
                    step="3" 
                    title="Watch and continue" 
                    description="Watch a 1-minute official clip preview and get direct links to continue watching on your favorite platforms." 
                />
            </div>
        </section>

        {{-- Feature highlights --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div class="space-y-8">
                <h2 class="text-3xl font-bold">Search beyond titles</h2>
                <p class="text-lg text-muted leading-relaxed">
                    Traditional search is limited to names. Movie Finder understands plot, mood, and specific visual details. Whether it's a cult classic or a new release, if you can describe it, we can find it.
                </p>
                <ul class="space-y-4">
                    <li class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-accent"></div>
                        <span class="text-text font-medium">AI-powered scene search</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-accent"></div>
                        <span class="text-text font-medium">Official clip previews</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-accent"></div>
                        <span class="text-text font-medium">Personal watchlist and history</span>
                    </li>
                </ul>
            </div>
            <div class="relative group">
                <x-scene-frame>
                    <div class="aspect-video bg-surface rounded-lg overflow-hidden border border-border flex items-center justify-center">
                        <span class="text-faint italic">AI Chat Interface Preview</span>
                    </div>
                </x-scene-frame>
            </div>
        </section>

        {{-- Plan comparison --}}
        <section id="pricing">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold mb-4">Choose your plan</h2>
                <p class="text-muted">Simple pricing for movie lovers and cinema pros.</p>
            </div>
            <x-plan-compare :plans="$plans" />
        </section>

        {{-- FAQ --}}
        <section class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold text-center mb-16">Frequently Asked Questions</h2>
            <div class="space-y-8">
                <div class="space-y-2">
                    <h4 class="text-lg font-bold">Is this a streaming site?</h4>
                    <p class="text-muted leading-relaxed">No. We are a discovery platform. We help you find what to watch and where to watch it on official services like Netflix, Disney+, and Apple TV.</p>
                </div>
                <div class="space-y-2">
                    <h4 class="text-lg font-bold">Where do the links go?</h4>
                    <p class="text-muted leading-relaxed">All links lead to official, legal platforms. We do not host movies or provide links to pirated content.</p>
                </div>
                <div class="space-y-2">
                    <h4 class="text-lg font-bold">Is the AI always right?</h4>
                    <p class="text-muted leading-relaxed">Our AI is highly accurate for well-known scenes and plot points. For very obscure titles, it provides the closest possible matches based on your description.</p>
                </div>
            </div>
        </section>

        {{-- Poster strip --}}
        @if($posters->isNotEmpty())
            <section class="overflow-hidden py-12">
                <div class="flex gap-4 animate-scroll whitespace-nowrap">
                    @foreach($posters->concat($posters) as $poster)
                        <div class="w-32 sm:w-48 shrink-0">
                            <img 
                                src="{{ str_starts_with($poster->poster_path, 'local:') ? \Illuminate\Support\Facades\Storage::disk('public')->url(substr($poster->poster_path, 6)) : 'https://image.tmdb.org/t/p/w342' . $poster->poster_path }}" 
                                alt="{{ $poster->title }}"
                                class="rounded-lg shadow-lg grayscale hover:grayscale-0 transition-all duration-500"
                                loading="lazy"
                            >
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

</x-app-layout>
