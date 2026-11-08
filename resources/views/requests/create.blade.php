<x-app-layout>
    <div class="py-8 sm:py-10 max-w-2xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">Request a title</h1>
                <p class="text-sm text-muted mt-1">Cannot find a movie or TV show? Request it and our team will look into adding it.</p>
            </div>
            <a href="{{ route('requests.index') }}" class="text-sm text-accent hover:underline">
                My requests
            </a>
        </div>

        @if ($maintenance ?? false)
            <x-under-maintenance feature="Title requests" />
        @else
        <div class="p-4 rounded-xl bg-card border border-border flex items-center justify-between text-sm">
            <span class="text-muted">Daily limit ({{ ucfirst($plan->value) }} plan):</span>
            <span class="font-medium text-text">
                {{ $usedToday }} / {{ $dailyLimit }} used today
                @if ($remainingToday > 0)
                    <span class="text-accent">({{ $remainingToday }} remaining)</span>
                @else
                    <span class="text-danger font-semibold">(Limit reached for today)</span>
                @endif
            </span>
        </div>

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-danger/10 border border-danger/30 text-sm text-danger space-y-1">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('requests.store') }}" class="space-y-6 bg-card border border-border rounded-2xl p-6 sm:p-8">
            @csrf

            {{-- Title input --}}
            <div>
                <x-input-label for="title" :value="__('Title')" />
                <x-text-input
                    id="title"
                    name="title"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('title', $prefilledTitle)"
                    placeholder="e.g. Inception"
                    required
                    autofocus
                    maxlength="200"
                />
                <x-input-error class="mt-2" :messages="$errors->get('title')" />
                <x-input-error class="mt-1" :messages="$errors->get('title_text')" />
            </div>

            {{-- Media type --}}
            <div>
                <x-input-label for="type" :value="__('Type')" />
                <select
                    id="type"
                    name="type"
                    class="mt-1 block w-full min-h-[44px] px-3 py-2 bg-card border border-border text-text rounded-xl text-[16px] sm:text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent"
                >
                    <option value="movie" @selected(old('type', $prefilledType) === 'movie')>Movie</option>
                    <option value="tv" @selected(old('type', $prefilledType) === 'tv')>TV Show</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('type')" />
                <x-input-error class="mt-1" :messages="$errors->get('media_type')" />
            </div>

            {{-- Release year (optional) --}}
            <div>
                <x-input-label for="year" :value="__('Release year (optional)')" />
                <x-text-input
                    id="year"
                    name="year"
                    type="number"
                    min="1888"
                    max="{{ (int) date('Y') + 5 }}"
                    class="mt-1 block w-full"
                    :value="old('year', $prefilledYear)"
                    placeholder="e.g. 2010"
                />
                <x-input-error class="mt-2" :messages="$errors->get('year')" />
                <x-input-error class="mt-1" :messages="$errors->get('release_year')" />
            </div>

            {{-- Note (optional) --}}
            <div>
                <x-input-label for="note" :value="__('Note (optional)')" />
                <textarea
                    id="note"
                    name="note"
                    rows="3"
                    maxlength="500"
                    placeholder="Optional details such as director, actor, or specific edition"
                    class="mt-1 block w-full px-3 py-2 bg-card border border-border text-text placeholder-muted rounded-xl text-[16px] sm:text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent"
                >{{ old('note') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('note')" />
                <p class="text-xs text-muted mt-1">Maximum 500 characters.</p>
            </div>

            {{-- Action buttons --}}
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('requests.index') }}" class="text-sm text-muted hover:text-text transition-colors">
                    Cancel
                </a>
                <x-button variant="primary" type="submit" :disabled="$remainingToday === 0">
                    Submit request
                </x-button>
            </div>
        </form>
        @endif
    </div>
</x-app-layout>
