@props([
    'startImmediately' => false,
])

<div
    x-data="guidedTour({ startImmediately: @js($startImmediately) })"
    x-cloak
    x-show="isActive"
    x-on:start-guided-tour.window="start()"
    x-on:skip-tour-prompt.window="recordOutcome('skipped')"
    x-on:keydown.escape.window="if (isActive) skip()"
    x-on:keydown.tab.prevent="if (isActive) handleTab($event)"
    class="relative z-50"
    role="dialog"
    aria-modal="true"
    aria-label="Guided tour"
>
    {{-- Spotlight cutout box --}}
    <div
        x-show="isActive && spotlightStyle.width > 0"
        class="fixed pointer-events-none rounded-xl border-2 border-accent z-50"
        :class="{ 'transition-all duration-200 ease-out': !prefersReducedMotion }"
        :style="`top: ${spotlightStyle.top}px; left: ${spotlightStyle.left}px; width: ${spotlightStyle.width}px; height: ${spotlightStyle.height}px; box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7);`"
    ></div>

    {{-- Tour Step Card --}}
    <div
        x-ref="stepCard"
        x-show="isActive"
        tabindex="-1"
        class="fixed z-50 p-5 rounded-2xl border border-border bg-card text-text shadow-2xl max-w-sm w-[calc(100%-2rem)] sm:w-96
               bottom-[calc(1rem+env(safe-area-inset-bottom,0px))] left-4 right-4 mx-auto
               sm:bottom-auto sm:right-auto sm:mx-0 outline-none"
        :style="desktopCardStyle"
        :class="{ 'transition-all duration-200 ease-out': !prefersReducedMotion }"
    >
        <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-accent" x-text="stepMarker"></span>
            <button
                type="button"
                x-on:click="skip()"
                class="text-xs font-medium text-muted hover:text-text min-h-[44px] min-w-[44px] inline-flex items-center justify-center -mr-2 focus:outline-none focus:ring-2 focus:ring-accent rounded"
                aria-label="Skip tour"
            >
                Skip
            </button>
        </div>

        <h4 class="text-base font-semibold text-text mb-1" x-text="currentStep?.title"></h4>
        <p class="text-sm text-muted mb-4" x-text="currentStep?.content"></p>

        <div class="flex items-center justify-between gap-3 pt-3 border-t border-border">
            <div>
                <x-button
                    type="button"
                    variant="secondary"
                    x-show="hasPrev"
                    x-on:click="prev()"
                    class="min-h-[44px] px-3.5 py-1.5 text-xs sm:text-sm"
                >
                    Back
                </x-button>
            </div>

            <div class="flex items-center gap-2">
                <x-button
                    type="button"
                    variant="primary"
                    x-ref="nextButton"
                    x-on:click="next()"
                    class="min-h-[44px] px-4 py-1.5 text-xs sm:text-sm"
                    x-text="isLast ? 'Finish' : 'Next'"
                >
                </x-button>
            </div>
        </div>
    </div>
</div>
