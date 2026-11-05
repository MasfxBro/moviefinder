@props([
    'people',
])

@php
    $directors = $people->whereIn('role', ['director', 'creator'])->values();
    $cast = $people->where('role', 'cast')->take(8)->values();
@endphp

@if ($directors->isNotEmpty() || $cast->isNotEmpty())
    <div class="space-y-6">
        {{-- Directors / Creators --}}
        @if ($directors->isNotEmpty())
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-faint mb-2">
                    {{ $directors->first()->role === 'creator' ? 'Created by' : 'Directed by' }}
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($directors as $director)
                        <a
                            href="{{ route('browse', ['director' => $director->name]) }}"
                            class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs sm:text-sm font-medium bg-card border border-border text-text hover:border-accent transition-colors"
                        >
                            {{ $director->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Top Cast --}}
        @if ($cast->isNotEmpty())
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-faint mb-2">
                    Top Cast
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                    @foreach ($cast as $member)
                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-card border border-border">
                            <div class="w-8 h-8 rounded-full bg-elevated border border-border flex items-center justify-center text-xs font-semibold text-text flex-shrink-0">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                            <span class="text-xs sm:text-sm font-medium text-text truncate" title="{{ $member->name }}">
                                {{ $member->name }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
