@props([
    'paginator',
])

@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="flex items-center justify-center gap-3 pt-2">
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-xl border border-border text-sm text-faint" aria-disabled="true">
                Previous
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-xl border border-border bg-card text-sm text-text hover:bg-elevated transition-colors">
                Previous
            </a>
        @endif

        <span class="text-sm text-muted">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-xl border border-border bg-card text-sm text-text hover:bg-elevated transition-colors">
                Next
            </a>
        @else
            <span class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-xl border border-border text-sm text-faint" aria-disabled="true">
                Next
            </span>
        @endif
    </nav>
@endif
