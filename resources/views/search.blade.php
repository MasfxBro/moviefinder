<x-app-layout>
    <div class="py-8 sm:py-10 space-y-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">
                Search
            </h1>
            <p class="text-sm text-muted mt-1">
                Search titles in our catalog
            </p>
        </div>

        <x-search-bar placeholder="Search titles" />

        @if (isset($results) && $results->isEmpty() && filled(request('q')))
            <x-empty-state
                title="We could not find that title yet"
                description="Try another title, or request it and we will look it up."
            >
                <x-slot:action>
                    <a href="{{ route('requests.create', ['title' => request('q')]) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-accent px-4 py-2 text-sm font-medium text-text transition-colors duration-150 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg">
                        Request this title
                    </a>
                </x-slot:action>
            </x-empty-state>
        @elseif (isset($results) && $results->isNotEmpty())
            <x-title-grid>
                @foreach ($results as $result)
                    <x-poster-card
                        :title="$result->title"
                        :year="$result->release_year"
                        :poster="$result->poster_path"
                        :url="url('/titles/'.$result->slug)"
                        :genres="$result->genres->pluck('name')->all()"
                        :mediaType="$result->media_type"
                        :title-id="$result->id"
                        :saved="in_array($result->id, $savedTitleIds, true)"
                    />
                @endforeach
            </x-title-grid>
        @endif
    </div>
</x-app-layout>
