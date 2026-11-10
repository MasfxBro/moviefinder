@props([
    'guest' => false,
])

<nav data-tour="navbar" x-data="{ mobileOpen: false, mobileSearchOpen: false }" x-on:keydown.escape.window="mobileOpen = false; mobileSearchOpen = false" class="sticky top-0 z-40 bg-bg/95 backdrop-blur border-b border-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        {{-- Brand / Logo --}}
        <div class="flex items-center gap-3">
            @if (! $guest && auth()->check())
                <button type="button" class="lg:hidden inline-flex items-center justify-center min-h-[44px] min-w-[44px] rounded-xl border border-border text-text" aria-label="Open navigation menu" aria-expanded="false" x-on:click="mobileOpen = ! mobileOpen" x-bind:aria-expanded="mobileOpen.toString()">
                    <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            @endif

            <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-text hover:opacity-90">
                {{ config('app.name', 'MovieFinder') }}
            </a>

            {{-- Main Navigation Links --}}
            <div data-tour="nav-links" class="hidden lg:flex items-center gap-6">
                <a href="{{ url('/') }}" class="text-sm font-medium text-text hover:text-accent transition-colors">
                    Home
                </a>
                <a href="{{ url('/browse?type=movie') }}" class="text-sm font-medium text-muted hover:text-text transition-colors">
                    Movies
                </a>
                <a href="{{ url('/browse?type=tv') }}" class="text-sm font-medium text-muted hover:text-text transition-colors">
                    TV Shows
                </a>
                <a href="{{ route('pricing') }}" class="text-sm font-medium text-muted hover:text-text transition-colors">
                    Pricing
                </a>
            </div>
        </div>

        {{-- Center: Search slot --}}
        @if (isset($search))
            <div data-tour="search" class="flex-1 max-w-md hidden lg:block">
                {{ $search }}
            </div>
        @elseif (! $guest && auth()->check())
            <div data-tour="search" class="flex-1 max-w-md hidden lg:block">
                <x-search-bar />
            </div>
        @endif

        {{-- Right: Actions & User Menu / Guest links --}}
        <div class="flex items-center gap-3">
            @if (! $guest && auth()->check())
                <button type="button" class="lg:hidden inline-flex items-center justify-center min-h-[44px] min-w-[44px] rounded-xl text-text hover:bg-elevated focus:outline-none focus:ring-2 focus:ring-accent" aria-label="{{ __('Search titles') }}" aria-expanded="false" x-bind:aria-expanded="mobileSearchOpen.toString()" x-on:click="mobileSearchOpen = ! mobileSearchOpen; if (mobileSearchOpen) $nextTick(() => $refs.mobileSearchInput?.focus())">
                    <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7" stroke-width="2" />
                        <path stroke-linecap="round" stroke-width="2" d="m16 16 4 4" />
                    </svg>
                </button>
                {{-- AI Chat Button Slot or default component --}}
                <div data-tour="ai-button" class="inline-flex">
                    @if (isset($aiButton))
                        {{ $aiButton }}
                    @else
                        <x-ai-nav-button />
                    @endif
                </div>

                {{-- User Avatar / Menu Slot --}}
                <div data-tour="avatar-menu" class="inline-flex">
                    @if (isset($avatar))
                        {{ $avatar }}
                    @else
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button type="button" class="w-9 h-9 rounded-full bg-elevated border border-border flex items-center justify-center text-text text-sm font-semibold hover:border-accent transition-colors focus:outline-none" aria-label="Open user menu">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-2 border-b border-border">
                                    <p class="text-sm font-medium text-text truncate">{{ auth()->user()->name }}</p>
                                    @if (auth()->user()->plan() !== \App\Enums\Plan::Free)
                                        <x-plan-badge :plan="strtoupper(auth()->user()->plan()->value)" />
                                    @endif
                                    <p class="text-xs text-muted truncate">{{ auth()->user()->email }}</p>
                                </div>

                                <x-dropdown-link :href="route('profile.edit')">
                                    Profile
                                </x-dropdown-link>
                                @if (auth()->user()->hasVerifiedEmail())
                                    <x-dropdown-link :href="route('watchlist.index')">
                                        Watchlist
                                    </x-dropdown-link>
                                @endif
                                <x-dropdown-link :href="route('history.index')">
                                    Search history
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('requests.index')">
                                    My requests
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 text-text transition duration-150 ease-in-out hover:bg-surface focus:bg-surface focus:outline-none">
                                        Log out
                                    </button>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    @endif
                </div>
            @else
                <div class="flex items-center gap-3">
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="text-sm font-medium text-muted hover:text-text transition-colors">
                            Log in
                        </a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-accent px-4 py-2 text-sm font-medium text-text transition-colors duration-150 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg">
                            Create account
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if (! $guest && auth()->check())
        <div x-cloak x-show="mobileSearchOpen" x-transition x-ref="mobileSearch" class="lg:hidden px-4 pb-3">
            @if (isset($search))
                {{ $search }}
            @else
                <x-search-bar input-ref="mobileSearchInput" />
            @endif
        </div>

        <template x-teleport="body">
            <div x-cloak x-show="mobileOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Navigation menu">
                <div
                    x-show="mobileOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-black/75"
                    x-on:click="mobileOpen = false"
                ></div>

                <aside
                    x-show="mobileOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="-translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="-translate-x-full"
                    class="fixed inset-y-0 left-0 z-10 h-full w-72 max-w-[85vw] bg-[#111113] border-r border-border p-5 flex flex-col gap-6 shadow-2xl overflow-y-auto"
                >
                    <div class="flex items-center justify-between">
                        <a href="{{ url('/') }}" class="text-xl font-bold text-text">{{ config('app.name', 'MovieFinder') }}</a>
                        <button type="button" class="min-h-[44px] min-w-[44px] rounded-xl text-text hover:text-accent flex items-center justify-center text-2xl font-light" aria-label="Close navigation menu" x-on:click="mobileOpen = false">×</button>
                    </div>
                    <nav class="flex flex-col gap-1" aria-label="Mobile navigation">
                        <a href="{{ url('/') }}" class="min-h-[44px] flex items-center text-sm font-medium text-text hover:text-accent">Home</a>
                        <a href="{{ url('/browse?type=movie') }}" class="min-h-[44px] flex items-center text-sm font-medium text-muted hover:text-text">Movies</a>
                        <a href="{{ url('/browse?type=tv') }}" class="min-h-[44px] flex items-center text-sm font-medium text-muted hover:text-text">TV Shows</a>
                        <a href="{{ route('pricing') }}" class="min-h-[44px] flex items-center text-sm font-medium text-muted hover:text-text">Pricing</a>
                        <a href="{{ route('history.index') }}" class="min-h-[44px] flex items-center text-sm font-medium text-muted hover:text-text">Search history</a>
                        <a href="{{ route('requests.index') }}" class="min-h-[44px] flex items-center text-sm font-medium text-muted hover:text-text">My requests</a>
                    </nav>
                    <form method="POST" action="{{ route('logout') }}" class="mt-auto">
                        @csrf
                        <x-button variant="secondary" type="submit" class="w-full">Log out</x-button>
                    </form>
                </aside>
            </div>
        </template>
        <template x-teleport="body">
            <x-ai-chat-panel />
        </template>
    @endif
</nav>
