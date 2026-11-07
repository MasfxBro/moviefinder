<x-app-layout>
    <div class="py-8 sm:py-10 space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">
                    @if (($filters['type'] ?? null) === 'movie')
                        Movies
                    @elseif (($filters['type'] ?? null) === 'tv')
                        TV Shows
                    @else
                        Browse
                    @endif
                </h1>
                <p class="text-sm text-muted mt-1">
                    {{ $titles->total() }} {{ $titles->total() === 1 ? 'title' : 'titles' }}
                </p>
            </div>
        </div>

        <x-filter-bar
            :action="route('browse')"
            :filters="$filters"
            :genres="$genres"
            :chips="$chips"
        />

        @if ($titles->isEmpty())
            <x-empty-state
                title="We could not find that title yet"
                description="Try fewer filters, or request the title and we will look it up."
            >
                @if (Route::has('requests.create'))
                    <x-slot:action>
                        <x-button variant="primary" onclick="window.location.href='{{ route('requests.create') }}'">
                            Request this title
                        </x-button>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @else
            <x-title-grid>
                @foreach ($titles as $title)
                    <x-poster-card
                        :title="$title->title"
                        :year="$title->release_year"
                        :poster="$title->poster_path"
                        :url="url('/titles/'.$title->slug)"
                        :genres="$title->genres->pluck('name')->all()"
                        :mediaType="$title->media_type"
                        :title-id="$title->id"
                        :saved="in_array($title->id, $savedTitleIds, true)"
                    />
                @endforeach
            </x-title-grid>

            <x-pagination :paginator="$titles" />
        @endif
    </div>
</x-app-layout>
