<x-app-layout>
    <div class="py-8 sm:py-10 max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-text">Feedback</h1>
            <p class="text-sm text-muted mt-1">Send us your critique, suggestions, or issues to help us improve.</p>
        </div>

        @if ($maintenance ?? false)
            <x-under-maintenance feature="Feedback" />
        @elseif (session('status') === 'thank-you')
            <div class="p-8 rounded-2xl bg-card border border-border scene-frame text-center space-y-4">
                <div class="scene-frame-bracket scene-frame-tl"></div>
                <div class="scene-frame-bracket scene-frame-tr"></div>
                <div class="scene-frame-bracket scene-frame-bl"></div>
                <div class="scene-frame-bracket scene-frame-br"></div>

                <div class="text-accent mb-2">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h2 class="text-xl font-semibold text-text">Thank you for your feedback!</h2>
                <p class="text-sm text-muted max-w-md mx-auto">
                    Your response has been recorded. Our team will review your message.
                </p>

                @if (session('submitted_message'))
                    <div class="mt-4 p-4 rounded-xl bg-surface border border-border text-xs text-muted text-left font-mono">
                        {{ session('submitted_message') }}
                    </div>
                @endif

                <div class="pt-4 flex justify-center gap-3">
                    <a href="{{ route('feedback.create') }}" class="px-4 py-2 rounded-xl bg-accent text-white text-sm font-medium hover:bg-accent/90 transition-colors">
                        Send more feedback
                    </a>
                    <a href="{{ route('home') }}" class="px-4 py-2 rounded-xl bg-surface border border-border text-text text-sm font-medium hover:bg-card transition-colors">
                        Return home
                    </a>
                </div>
            </div>
        @else
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-danger/10 border border-danger/30 text-sm text-danger space-y-1">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('feedback.store') }}" class="space-y-6 bg-card border border-border rounded-2xl p-6 sm:p-8">
                @csrf

                {{-- Honeypot fields --}}
                <div class="hidden" aria-hidden="true" style="display: none;">
                    <input type="text" name="website" tabindex="-1" autocomplete="off" value="" />
                    <input type="text" name="honeypot" tabindex="-1" autocomplete="off" value="" />
                </div>

                {{-- Type --}}
                <div>
                    <x-input-label for="type" :value="__('Feedback Type')" />
                    <select
                        id="type"
                        name="type"
                        required
                        class="mt-1 block w-full rounded-xl bg-surface border border-border px-4 py-2.5 text-sm text-text focus:outline-none focus:ring-2 focus:ring-accent"
                    >
                        <option value="suggestion" {{ old('type') === 'suggestion' ? 'selected' : '' }}>Suggestion</option>
                        <option value="bug" {{ old('type') === 'bug' ? 'selected' : '' }}>Bug report</option>
                        <option value="content" {{ old('type') === 'content' ? 'selected' : '' }}>Content issue</option>
                        <option value="praise" {{ old('type') === 'praise' ? 'selected' : '' }}>Praise</option>
                        <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                {{-- Message --}}
                <div>
                    <x-input-label for="message" :value="__('Message (10 to 1000 characters)')" />
                    <textarea
                        id="message"
                        name="message"
                        rows="5"
                        required
                        minlength="10"
                        maxlength="1000"
                        placeholder="Please describe your suggestion, question, or problem in detail..."
                        class="mt-1 block w-full rounded-xl bg-surface border border-border p-3 text-sm text-text focus:outline-none focus:ring-2 focus:ring-accent"
                    >{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-2" />
                </div>

                {{-- Rating --}}
                <div>
                    <x-input-label for="rating" :value="__('Rating (optional, 1 to 5)')" />
                    <select
                        id="rating"
                        name="rating"
                        class="mt-1 block w-full rounded-xl bg-surface border border-border px-4 py-2.5 text-sm text-text focus:outline-none focus:ring-2 focus:ring-accent"
                    >
                        <option value="">No rating</option>
                        <option value="5" {{ old('rating') === '5' ? 'selected' : '' }}>5 - Excellent</option>
                        <option value="4" {{ old('rating') === '4' ? 'selected' : '' }}>4 - Good</option>
                        <option value="3" {{ old('rating') === '3' ? 'selected' : '' }}>3 - Average</option>
                        <option value="2" {{ old('rating') === '2' ? 'selected' : '' }}>2 - Poor</option>
                        <option value="1" {{ old('rating') === '1' ? 'selected' : '' }}>1 - Very poor</option>
                    </select>
                    <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                </div>

                {{-- Page path (same-origin page path) --}}
                <div>
                    <x-input-label for="page_path" :value="__('Page Path (optional)')" />
                    <x-text-input
                        id="page_path"
                        name="page_path"
                        type="text"
                        maxlength="200"
                        value="{{ old('page_path', $pagePath ?? '') }}"
                        placeholder="/browse"
                        class="mt-1 block w-full"
                    />
                    <p class="text-xs text-muted mt-1">Relative path on this website related to your feedback.</p>
                    <x-input-error :messages="$errors->get('page_path')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('home') }}" class="px-4 py-2 text-sm text-muted hover:text-text transition-colors">
                        Cancel
                    </a>
                    <x-primary-button>
                        Send feedback
                    </x-primary-button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
