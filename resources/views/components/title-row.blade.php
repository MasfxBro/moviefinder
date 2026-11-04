@props([
    'title',
    'viewAllUrl' => null,
])

<div x-data="{
    scroll(direction) {
        const container = this.$refs.container;
        const scrollAmount = container.clientWidth * 0.75;
        container.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }
}" class="relative flex flex-col gap-4">
    {{-- Row Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-text tracking-tight">
            {{ $title }}
        </h2>

        <div class="flex items-center gap-2">
            @if($viewAllUrl)
                <a href="{{ $viewAllUrl }}" class="text-xs font-medium text-muted hover:text-accent transition-colors mr-2">
                    See all
                </a>
            @endif

            {{-- Arrow buttons (Desktop) --}}
            <button
                @click="scroll(-1)"
                type="button"
                aria-label="Scroll left"
                class="hidden sm:flex items-center justify-center w-8 h-8 rounded-full bg-card border border-border text-muted hover:text-text hover:border-faint transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <button
                @click="scroll(1)"
                type="button"
                aria-label="Scroll right"
                class="hidden sm:flex items-center justify-center w-8 h-8 rounded-full bg-card border border-border text-muted hover:text-text hover:border-faint transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Items Container --}}
    <div
        x-ref="container"
        class="flex gap-4 overflow-x-auto scrollbar-none snap-x snap-mandatory py-1 -mx-4 px-4 sm:mx-0 sm:px-0"
    >
        {{ $slot }}
    </div>
</div>
