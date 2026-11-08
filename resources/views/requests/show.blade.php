<x-app-layout>
    <div class="py-8 sm:py-10 max-w-2xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('requests.index') }}" class="text-sm text-accent hover:underline flex items-center gap-1">
                &larr; Back to my requests
            </a>

            @php
                $statusBadge = match($titleRequest->status->value) {
                    'pending' => 'bg-amber-500/15 text-amber-400 border border-amber-500/30',
                    'approved' => 'bg-accent/15 text-accent border border-accent/30',
                    'added' => 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30',
                    'rejected' => 'bg-red-500/15 text-red-400 border border-red-500/30',
                    default => 'bg-surface text-muted border border-border',
                };
            @endphp
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold capitalize {{ $statusBadge }}">
                {{ $titleRequest->status->value }}
            </span>
        </div>

        <div class="bg-card border border-border rounded-2xl p-6 sm:p-8 space-y-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">
                    {{ $titleRequest->title_text }}
                </h1>
                <div class="flex items-center gap-2 mt-2 text-sm text-muted">
                    <span class="uppercase font-medium">{{ $titleRequest->media_type->value }}</span>
                    @if ($titleRequest->release_year)
                        <span>·</span>
                        <span>{{ $titleRequest->release_year }}</span>
                    @endif
                    <span>·</span>
                    <span>Requested {{ $titleRequest->created_at->format('M d, Y') }}</span>
                </div>
            </div>

            @if ($titleRequest->note)
                <div class="p-4 rounded-xl bg-surface border border-border space-y-1">
                    <h2 class="text-xs font-semibold uppercase text-muted tracking-wider">Your note</h2>
                    <p class="text-sm text-text whitespace-pre-line">{{ $titleRequest->note }}</p>
                </div>
            @endif

            @if ($titleRequest->admin_note)
                <div class="p-4 rounded-xl bg-surface border border-border space-y-1">
                    <h2 class="text-xs font-semibold uppercase text-muted tracking-wider">Review note</h2>
                    <p class="text-sm text-text whitespace-pre-line">{{ $titleRequest->admin_note }}</p>
                </div>
            @endif

            @if ($titleRequest->status->value === 'added' && $titleRequest->resolvedTitle)
                <div class="pt-4 border-t border-border flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-text">This title is now available in the catalog!</p>
                        <p class="text-xs text-muted">You can view details, trailers, and clips.</p>
                    </div>
                    <a href="{{ url('/titles/' . $titleRequest->resolvedTitle->slug) }}">
                        <x-button variant="primary">
                            View title
                        </x-button>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
