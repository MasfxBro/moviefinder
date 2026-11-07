@props([
    'class' => '',
])

<button
    type="button"
    x-data
    x-on:click="$dispatch('toggle-ai-chat')"
    class="relative inline-flex items-center justify-center gap-2 px-3 py-1.5 min-h-[44px] min-w-[44px] rounded-xl border border-border bg-card text-text hover:bg-elevated hover:border-accent/40 focus:outline-none focus:ring-2 focus:ring-accent transition-colors {{ $class }}"
    aria-label="{{ __('AI Chat') }}"
    title="{{ __('AI Chat') }}"
>
    <svg class="h-5 w-5 text-current shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 8V5h4" />
        <path d="M16 5h4v3" />
        <path d="M20 13v3h-4" />
        <path d="M9 16H4v-3" />
        <path d="M5 16l-2 4 4-2" />
    </svg>
    <span class="hidden lg:inline text-sm font-medium">{{ __('AI Chat') }}</span>
</button>
