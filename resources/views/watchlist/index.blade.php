<x-app-layout>
    <div class="py-8 sm:py-10 space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">My Watchlist</h1>
                <p class="text-sm text-muted mt-1">{{ $items->total() }} {{ $items->total() === 1 ? 'title' : 'titles' }} saved</p>
            </div>
        </div>

        @if ($items->isEmpty())
            <x-empty-state title="Your watchlist is empty" description="Save titles from their cards or detail pages to keep them here." />
        @else
            <x-title-grid>
                @foreach ($items as $item)
                    <x-poster-card
                        :title="$item->title->title"
                        :title-id="$item->title_id"
                        :saved="true"
                        :year="$item->title->release_year"
                        :poster="$item->title->poster_path"
                        :url="route('titles.show', ['slug' => $item->title->slug])"
                        :genres="$item->title->genres->pluck('name')->all()"
                        :media-type="$item->title->media_type"
                    />
                @endforeach
            </x-title-grid>

            <x-pagination :paginator="$items" />
        @endif
    </div>
</x-app-layout>
