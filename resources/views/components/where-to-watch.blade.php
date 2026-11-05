@props([
    'providers' => [],
])

@if (! empty($providers))
    <div class="space-y-4">
        <div class="flex flex-wrap gap-2">
            @foreach ($providers as $provider)
                @php
                    $name = is_array($provider) ? ($provider['provider_name'] ?? '') : ($provider->provider_name ?? '');
                @endphp
                @if (filled($name))
                    <span class="inline-flex items-center px-3.5 py-2 rounded-xl bg-surface border border-border text-sm font-medium text-text">
                        {{ $name }}
                    </span>
                @endif
            @endforeach
        </div>

        <p class="text-xs text-faint">
            Where-to-watch data provided by JustWatch.
        </p>
    </div>
@else
    <div class="rounded-xl bg-surface border border-border p-6 text-center text-sm text-faint">
        No streaming availability found in Indonesia for this title.
    </div>
@endif
