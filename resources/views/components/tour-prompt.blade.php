<div
    x-data="{ visible: true }"
    x-cloak
    x-show="visible"
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-3"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-3"
    class="fixed bottom-[calc(1rem+env(safe-area-inset-bottom,0px))] right-4 sm:right-6 z-40 max-w-md w-[calc(100%-2rem)] sm:w-auto p-5 rounded-2xl border border-border bg-card text-text shadow-2xl"
    role="region"
    aria-label="Guided tour invitation"
>
    <div class="flex flex-col gap-4">
        <div class="space-y-1">
            <h3 class="text-base font-semibold text-text">
                Welcome to {{ config('app.name', 'MovieFinder') }}
            </h3>
            <p class="text-sm text-muted">
                Would you like a short guided tour of the website?
            </p>
        </div>

        <div class="flex items-center justify-end gap-3">
            <x-button
                type="button"
                variant="ghost"
                x-on:click="visible = false; $dispatch('skip-tour-prompt')"
                class="min-h-[44px] px-3.5 py-1.5 text-xs sm:text-sm"
            >
                Skip
            </x-button>

            <x-button
                type="button"
                variant="primary"
                x-on:click="visible = false; $dispatch('start-guided-tour')"
                class="min-h-[44px] px-3.5 py-1.5 text-xs sm:text-sm"
            >
                Start the tour
            </x-button>
        </div>
    </div>
</div>
