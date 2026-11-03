@props([
    'type' => 'info',
    'message' => '',
])

@php
    $typeClasses = match($type) {
        'success' => 'bg-card border-success text-text',
        'error', 'danger' => 'bg-card border-danger text-text',
        default => 'bg-card border-border text-text',
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 4000)"
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    {{ $attributes->merge(['class' => 'fixed bottom-4 right-4 z-50 flex items-center justify-between min-w-[280px] max-w-md p-4 rounded-xl border shadow-lg ' . $typeClasses]) }}
    role="alert"
>
    <div class="text-sm font-medium">
        {{ $message ?: $slot }}
    </div>
    <button
        type="button"
        @click="show = false"
        class="ml-4 text-muted hover:text-text transition-colors"
        aria-label="Close notification"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
