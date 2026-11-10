@props([
    'title',
    'year' => null,
    'poster' => null,
    'url' => '#',
    'genres' => [],
    'mediaType' => null,
    'titleId' => null,
    'saved' => false,
    'aspect' => '2/3',
])

@php
    $imageBase = config('services.tmdb.image_base', 'https://image.tmdb.org/t/p/');
    $posterUrl = $poster
        ? (str_starts_with($poster, 'local:')
            ? \Illuminate\Support\Facades\Storage::disk('public')->url(substr($poster, 6))
            : $imageBase . 'w342' . $poster)
        : null;
    $fullTitle = $title . ($year ? " ({$year})" : "");
@endphp

<div {{ $attributes->merge(['class' => 'group relative flex flex-col gap-3']) }}>
    {{-- Poster Container --}}
    <div @class([
        'relative overflow-hidden rounded-xl bg-card border border-border shadow-sm transition-all duration-200',
        'group-hover:-translate-y-1 group-hover:shadow-md group-focus-within:-translate-y-1 group-focus-within:shadow-md',
        'aspect-[2/3]' => $aspect === '2/3',
        'aspect-[16/9]' => $aspect === '16/9',
    ])>
        <a href="{{ $url }}" class="block w-full h-full" aria-label="{{ $fullTitle }}">
            @if($posterUrl)
                <img
                    src="{{ $posterUrl }}"
                    alt="Poster of {{ $title }}"
                    loading="lazy"
                    class="h-full w-full object-cover transition-opacity duration-300"
                >
            @else
                <div class="flex items-center justify-center w-full h-full text-faint">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif
        </a>

        {{-- Quick Action Slot --}}
        @if($titleId && auth()->user()?->hasVerifiedEmail())
            <div class="absolute top-2 right-2 opacity-100 sm:opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-200">
                <x-watchlist-toggle :title-id="$titleId" :saved="$saved" compact />
            </div>
        @elseif(isset($watchlist))
            <div class="absolute top-2 right-2 opacity-100 sm:opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-200">
                {{ $watchlist }}
            </div>
        @endif

        {{-- Media Type Badge (optional, but useful) --}}
        @if($mediaType)
            <div class="absolute bottom-2 left-2 px-1.5 py-0.5 rounded bg-black/60 backdrop-blur-md text-[10px] font-bold text-white uppercase tracking-wider">
                {{ $mediaType === 'movie' ? 'Movie' : 'TV' }}
            </div>
        @endif
    </div>

    {{-- Info --}}
    <div class="flex flex-col gap-1 min-w-0">
        <a href="{{ $url }}" class="block group/title" title="{{ $fullTitle }}">
            <h3 class="text-sm font-medium text-text line-clamp-2 leading-snug group-hover/title:text-accent transition-colors">
                {{ $title }}
            </h3>
        </a>

        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
            @if($year)
                <span>{{ $year }}</span>
            @endif

            @if($year && !empty($genres))
                <span class="w-1 h-1 rounded-full bg-border"></span>
            @endif

            @foreach(array_slice($genres, 0, 2) as $genre)
                <span class="truncate max-w-[80px]">{{ $genre }}</span>
                @if(!$loop->last)
                    <span class="text-faint">/</span>
                @endif
            @endforeach
        </div>
    </div>
</div>
