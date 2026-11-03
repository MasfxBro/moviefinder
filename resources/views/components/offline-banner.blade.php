<div x-data="offlineNotice()" x-cloak>
    <div
        x-show="offline"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-y-full opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="-translate-y-full opacity-0"
        class="bg-warning/20 border-b border-warning/40 text-warning px-4 py-2 text-center text-sm font-medium"
        role="alert"
    >
        You are offline. Some features will not work until you are back online.
    </div>

    <div
        x-show="toast"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-4 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-4 opacity-0"
        class="fixed bottom-4 right-4 z-50 bg-surface border border-success/40 text-success px-4 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center gap-2"
        role="status"
    >
        <svg class="w-4 h-4 text-success shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>Back online</span>
    </div>
</div>
