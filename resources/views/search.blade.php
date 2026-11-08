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
                    <a href="{{ route('requests.create', ['title' => request('q')]) }}">
                        <x-button variant="primary" onclick="window.location.href='{{ route('requests.create', ['title' => request('q')]) }}'">
                            Request this title
                        </x-button>
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
