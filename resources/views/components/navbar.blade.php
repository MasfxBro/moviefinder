@props([
    'guest' => false,
])

<nav class="sticky top-0 z-40 bg-bg/95 backdrop-blur border-b border-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        {{-- Brand / Logo --}}
        <div class="flex items-center gap-8">
            <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-text hover:opacity-90">
                {{ config('app.name', 'MovieFinder') }}
            </a>

            {{-- Main Navigation Links --}}
            <div class="hidden md:flex items-center gap-6">
                <a href="{{ url('/') }}" class="text-sm font-medium text-text hover:text-accent transition-colors">
                    Home
                </a>
                <a href="{{ url('/browse?type=movie') }}" class="text-sm font-medium text-muted hover:text-text transition-colors">
                    Movies
                </a>
                <a href="{{ url('/browse?type=tv') }}" class="text-sm font-medium text-muted hover:text-text transition-colors">
                    TV Shows
                </a>
            </div>
        </div>

        {{-- Center: Search slot --}}
        @if (isset($search))
            <div class="flex-1 max-w-md hidden sm:block">
                {{ $search }}
            </div>
        @elseif (! $guest && auth()->check())
            <div class="flex-1 max-w-md hidden sm:block">
                <x-search-bar />
            </div>
        @endif

        {{-- Right: Actions & User Menu / Guest links --}}
        <div class="flex items-center gap-3">
            @if (! $guest && auth()->check())
                {{-- AI Chat Button Slot or default component --}}
                @if (isset($aiButton))
                    {{ $aiButton }}
                @else
                    <button
                        type="button"
                        class="relative inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-border bg-card text-text text-sm font-medium hover:bg-elevated transition-colors"
                        aria-label="AI Chat"
                    >
                        <div class="relative w-4 h-4 border border-border flex items-center justify-center">
                            <span class="text-[9px] leading-none font-bold">AI</span>
                        </div>
                        <span class="hidden md:inline">AI Chat</span>
                    </button>
                @endif

                {{-- User Avatar / Menu Slot --}}
                @if (isset($avatar))
                    {{ $avatar }}
                @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('profile.edit') }}" class="w-9 h-9 rounded-full bg-elevated border border-border flex items-center justify-center text-text text-sm font-semibold hover:border-accent transition-colors">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </a>
                    </div>
                @endif
            @else
                <div class="flex items-center gap-3">
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="text-sm font-medium text-muted hover:text-text transition-colors">
                            Log in
                        </a>
                    @endif
                    @if (Route::has('register'))
                        <x-button variant="primary" onclick="window.location.href='{{ route('register') }}'">
                            Create account
                        </x-button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</nav>
