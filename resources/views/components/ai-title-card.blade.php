@props([
    'title' => '',
    'year' => null,
    'directors' => [],
    'overview' => '',
    'reason' => '',
    'url' => '#',
])

<article {{ $attributes->merge(['class' => 'scene-frame bg-surface border border-border rounded-xl p-3 sm:p-4 flex flex-col gap-2 hover:border-accent/40 transition-colors']) }}>
    <div class="scene-frame-bracket scene-frame-tl"></div>
    <div class="scene-frame-bracket scene-frame-tr"></div>
    <div class="scene-frame-bracket scene-frame-bl"></div>
    <div class="scene-frame-bracket scene-frame-br"></div>

    <div class="flex items-start justify-between gap-2">
        <h5 class="text-sm font-semibold text-text leading-snug">
            <a href="{{ $url }}" class="hover:text-accent transition-colors">
                {{ $title }}
                @if ($year)
                    <span class="text-muted font-normal text-xs ml-1">({{ $year }})</span>
                @endif
            </a>
        </h5>
        <a href="{{ $url }}" class="shrink-0 text-xs text-accent hover:underline flex items-center gap-1 font-medium">
            <span>View</span>
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14" />
                <path d="m12 5 7 7-7 7" />
            </svg>
        </a>
    </div>

    @if (! empty($directors))
        <p class="text-xs text-muted">
            Directed by {{ is_array($directors) ? implode(', ', $directors) : $directors }}
        </p>
    @endif

    @if ($reason)
        <div class="rounded-lg bg-card/80 border border-border/80 px-2.5 py-1.5 text-xs text-text/90">
            <span class="text-muted font-medium">Why it matches:</span>
            <span>{{ $reason }}</span>
        </div>
    @endif

    @if ($overview)
        <p class="text-xs text-muted line-clamp-2 leading-relaxed">
            {{ $overview }}
        </p>
    @endif
</article>
