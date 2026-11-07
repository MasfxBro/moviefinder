<div
    x-data="aiChat({ variant: 'panel' })"
    x-on:toggle-ai-chat.window="toggle()"
    x-on:open-ai-chat.window="open()"
    x-on:close-ai-chat.window="requestClose()"
    x-on:keydown.escape.window="if (isOpen) { $event.preventDefault(); requestClose(); }"
    x-cloak
    x-show="isOpen"
    class="fixed inset-0 z-50 flex flex-col bg-card h-[100dvh] w-full lg:fixed lg:inset-auto lg:top-16 lg:right-6 lg:w-[440px] lg:h-[620px] lg:max-h-[calc(100vh-5rem)] lg:rounded-2xl lg:border lg:border-border lg:shadow-2xl overflow-hidden"
    role="dialog"
    aria-label="{{ __('AI Chat') }}"
    aria-modal="true"
>
    {{-- Header --}}
    <div class="h-14 px-4 border-b border-border bg-surface flex items-center justify-between shrink-0">
        <div class="flex items-center gap-2.5">
            <svg class="h-5 w-5 text-accent shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 8V5h4" />
                <path d="M16 5h4v3" />
                <path d="M20 13v3h-4" />
                <path d="M9 16H4v-3" />
                <path d="M5 16l-2 4 4-2" />
            </svg>
            <h3 class="text-sm font-bold tracking-tight text-text">
                {{ __('AI Chat') }}
            </h3>
        </div>

        <button
            type="button"
            x-on:click="requestClose()"
            class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] rounded-xl text-muted hover:text-text hover:bg-elevated focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
            aria-label="{{ __('Close AI Chat') }}"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
    </div>

    {{-- Main chat body --}}
    <x-ai-chat variant="panel" />

    {{-- Exit Warning Modal (shown only when hasMessages is true) --}}
    <div
        x-cloak
        x-show="exitModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-bg/80 backdrop-blur-sm"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="exit-modal-title"
        aria-describedby="exit-modal-description"
    >
        <div
            x-show="exitModalOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-sm rounded-2xl border border-border bg-elevated p-6 shadow-2xl flex flex-col gap-4"
        >
            <div class="flex flex-col gap-1">
                <h4 id="exit-modal-title" class="text-base font-semibold text-text">
                    {{ __('Leave this chat?') }}
                </h4>
                <p id="exit-modal-description" class="text-sm text-muted leading-relaxed">
                    {{ __('Your conversation with the AI will be lost.') }}
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    x-on:click="cancelLeave()"
                    class="px-4 py-2 rounded-xl border border-border bg-card text-sm font-medium text-text hover:bg-surface focus:outline-none focus:ring-2 focus:ring-accent min-h-[44px]"
                >
                    {{ __('Stay') }}
                </button>
                <button
                    type="button"
                    x-on:click="confirmLeave()"
                    class="px-4 py-2 rounded-xl bg-accent text-sm font-medium text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent min-h-[44px]"
                >
                    {{ __('Leave') }}
                </button>
            </div>
        </div>
    </div>
</div>
