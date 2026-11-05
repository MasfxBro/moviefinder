@props([
    'title',
])

@php
    $runtimeFormatted = null;
    if ($title->runtime_minutes) {
        $hours = intdiv($title->runtime_minutes, 60);
        $minutes = $title->runtime_minutes % 60;
        if ($hours > 0 && $minutes > 0) {
            $runtimeFormatted = "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            $runtimeFormatted = "{$hours}h";
        } else {
            $runtimeFormatted = "{$minutes}m";
        }
    }

    $tvSeriesCount = null;
    if ($title->media_type === 'tv') {
        $parts = [];
        if ($title->seasons_count) {
            $parts[] = $title->seasons_count . ' ' . ($title->seasons_count === 1 ? 'Season' : 'Seasons');
        }
        if ($title->episodes_count) {
            $parts[] = $title->episodes_count . ' ' . ($title->episodes_count === 1 ? 'Episode' : 'Episodes');
        }
        if (! empty($parts)) {
            $tvSeriesCount = implode(' · ', $parts);
        }
    }
@endphp

<div class="flex flex-wrap items-center gap-2">
    {{-- Media Type --}}
    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-semibold bg-surface border border-border text-text uppercase tracking-wider">
        {{ $title->media_type === 'movie' ? 'Movie' : 'TV Show' }}
    </span>

    {{-- Release Year --}}
    @if ($title->release_year)
        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-surface border border-border text-muted">
            {{ $title->release_year }}
        </span>
    @endif

    {{-- Runtime (Movies / TV when available) --}}
    @if ($runtimeFormatted)
        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-surface border border-border text-muted">
            {{ $runtimeFormatted }}
        </span>
    @endif

    {{-- Seasons / Episodes (TV) --}}
    @if ($tvSeriesCount)
        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-surface border border-border text-muted">
            {{ $tvSeriesCount }}
        </span>
    @endif

    {{-- Rating --}}
    @if ($title->vote_average && (float) $title->vote_average > 0)
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-semibold bg-surface border border-border text-warning">
            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            <span>{{ number_format((float) $title->vote_average, 1) }}</span>
            @if ($title->vote_count)
                <span class="text-faint font-normal">({{ number_format($title->vote_count) }})</span>
            @endif
        </span>
    @endif

    {{-- Original Language --}}
    @if (filled($title->original_language))
        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-surface border border-border text-muted uppercase">
            {{ $title->original_language }}
        </span>
    @endif

    {{-- Genres --}}
    @foreach ($title->genres as $genre)
        <a
            href="{{ route('browse', ['genres' => [$genre->id]]) }}"
            class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-surface border border-border text-muted hover:text-text hover:border-accent transition-colors"
        >
            {{ $genre->name }}
        </a>
    @endforeach
</div>
