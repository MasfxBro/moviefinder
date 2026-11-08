<div
    x-data="updateNotice({{ (int) config('app.update_poll_minutes', 5) }})"
    x-cloak
    x-show="show"
    x-on:keydown.escape.window="if (show) { later(); }"
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-3"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-3"
    class="fixed bottom-[calc(1rem+env(safe-area-inset-bottom,0px))] left-4 sm:left-6 z-40 max-w-sm w-[calc(100%-2rem)] sm:w-auto p-4 rounded-2xl border border-border bg-card text-text shadow-2xl"
    role="alert"
    aria-live="polite"
>
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <p class="text-sm font-medium text-text">
            {{ __('A new version is available') }}
        </p>

        <div class="flex items-center gap-2 self-end sm:self-auto">
            <x-button
                type="button"
                variant="secondary"
                x-on:click="later()"
                class="min-h-[44px] px-3.5 py-1.5 text-xs sm:text-sm"
            >
                {{ __('Later') }}
            </x-button>

            <x-button
                type="button"
                variant="primary"
                x-on:click="update()"
                class="min-h-[44px] px-3.5 py-1.5 text-xs sm:text-sm"
            >
                {{ __('Update') }}
            </x-button>
        </div>
    </div>
</div>
