<x-app-layout>
    <div class="py-8 sm:py-10 space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">Search history</h1>
                <p class="text-sm text-muted mt-1">Your recent searches across MovieFinder</p>
            </div>

            @if ($history->isNotEmpty())
                <form method="POST" action="{{ route('history.destroy') }}" onsubmit="return confirm('Clear your entire search history?');">
                    @csrf
                    @method('DELETE')
                    <x-button variant="secondary" type="submit">Clear all</x-button>
                </form>
            @endif
        </div>

        @if (session('status'))
            <div class="p-4 rounded-xl bg-surface border border-border text-sm text-accent">
                {{ session('status') }}
            </div>
        @endif

        @if ($history->isEmpty())
            <x-empty-state
                title="No search history yet"
                description="Searches you perform will show here."
            />
        @else
            <div class="bg-card border border-border rounded-2xl divide-y divide-border overflow-hidden">
                @foreach ($history as $item)
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-surface/50 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item->type === 'ai' ? 'bg-accent/15 text-accent border border-accent/30' : 'bg-surface text-muted border border-border' }}">
                                {{ $item->type === 'ai' ? 'AI Chat' : 'Catalog Search' }}
                            </span>

                            @if ($item->type === 'normal')
                                <a href="{{ route('search', ['q' => $item->query]) }}" class="text-sm font-medium text-text hover:text-accent truncate">
                                    {{ $item->query }}
                                </a>
                            @else
                                <span class="text-sm text-text truncate">
                                    {{ $item->query }}
                                </span>
                            @endif
                        </div>

                        <span class="text-xs text-muted shrink-0">
                            {{ $item->created_at->diffForHumans() }}
                        </span>
                    </div>
                @endforeach
            </div>

            <x-pagination :paginator="$history" />
        @endif
    </div>
</x-app-layout>
