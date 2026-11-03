@php
    $seconds = isset($exception) && method_exists($exception, 'getHeaders') ? ($exception->getHeaders()['Retry-After'] ?? 60) : 60;
@endphp

<x-error-page
    code="429"
    title="Slow down a little"
    :message="'Too many requests. Try again in ' . $seconds . ' seconds.'"
>
    <div x-data="{ seconds: {{ (int) $seconds }} }" x-init="if (seconds > 0) { const timer = setInterval(() => { if (--seconds <= 0) clearInterval(timer) }, 1000) }">
        <button
            :disabled="seconds > 0"
            onclick="window.location.reload()"
            class="inline-flex items-center justify-center px-4 py-2 bg-accent text-white font-medium text-sm rounded-lg hover:bg-accent/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
            Try again
        </button>
    </div>
</x-error-page>
