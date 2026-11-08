<x-app-layout>
    <div class="py-8 sm:py-10 space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">My requests</h1>
                <p class="text-sm text-muted mt-1">Titles you have requested and their current review status</p>
            </div>

            <div class="flex items-center gap-4">
                <span class="text-xs sm:text-sm text-muted">
                    Today: <span class="text-text font-medium">{{ $usedToday }} / {{ $dailyLimit }}</span>
                </span>
                <a href="{{ route('requests.create') }}">
                    <x-button variant="primary">
                        Request a title
                    </x-button>
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="p-4 rounded-xl bg-surface border border-accent/40 text-sm text-accent">
                {{ session('status') }}
            </div>
        @endif

        @if ($requests->isEmpty())
            <x-empty-state
                title="No title requests yet"
                description="If you cannot find a movie or TV show in our catalog, you can request it here."
            >
                <x-slot:action>
                    <a href="{{ route('requests.create') }}">
                        <x-button variant="primary">
                            Request a title
                        </x-button>
                    </a>
                </x-slot:action>
            </x-empty-state>
        @else
            <div class="bg-card border border-border rounded-2xl divide-y divide-border overflow-hidden">
                @foreach ($requests as $requestItem)
                    <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-surface/50 transition-colors">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('requests.show', $requestItem) }}" class="text-base font-semibold text-text hover:text-accent truncate">
                                    {{ $requestItem->title_text }}
                                </a>
                                @if ($requestItem->release_year)
                                    <span class="text-sm text-muted">({{ $requestItem->release_year }})</span>
                                @endif
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs uppercase font-medium bg-surface text-muted border border-border">
                                    {{ $requestItem->media_type->value }}
                                </span>
                            </div>

                            @if ($requestItem->note)
                                <p class="text-xs text-muted truncate max-w-xl">
                                    Note: {{ $requestItem->note }}
                                </p>
                            @endif

                            @if ($requestItem->admin_note)
                                <p class="text-xs text-text/80 bg-surface/70 px-2.5 py-1 rounded-lg border border-border inline-block mt-1">
                                    <span class="font-medium text-muted">Review note:</span> {{ $requestItem->admin_note }}
                                </p>
                            @endif

                            @if ($requestItem->status->value === 'added' && $requestItem->resolvedTitle)
                                <div class="mt-1">
                                    <a href="{{ url('/titles/' . $requestItem->resolvedTitle->slug) }}" class="text-xs text-accent hover:underline inline-flex items-center gap-1">
                                        View title in catalog &rarr;
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center sm:flex-col sm:items-end justify-between sm:justify-center gap-2 shrink-0">
                            {{-- Status Badge --}}
                            @php
                                $statusBadge = match($requestItem->status->value) {
                                    'pending' => 'bg-amber-500/15 text-amber-400 border border-amber-500/30',
                                    'approved' => 'bg-accent/15 text-accent border border-accent/30',
                                    'added' => 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30',
                                    'rejected' => 'bg-red-500/15 text-red-400 border border-red-500/30',
                                    default => 'bg-surface text-muted border border-border',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize {{ $statusBadge }}">
                                {{ $requestItem->status->value }}
                            </span>

                            <span class="text-xs text-muted">
                                {{ $requestItem->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <x-pagination :paginator="$requests" />
        @endif
    </div>
</x-app-layout>
