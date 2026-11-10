@php
    $seconds = isset($exception) && method_exists($exception, 'getHeaders') ? ($exception->getHeaders()['Retry-After'] ?? 60) : 60;
@endphp

<x-error-page
    code="429"
    title="Slow down a little"
    :message="'Too many requests. Try again in ' . $seconds . ' seconds.'"
>
    <div x-data="{ seconds: {{ (int) $seconds }} }" x-init="if (seconds > 0) { const timer = setInterval(() => { if (--seconds <= 0) clearInterval(timer) }, 1000) }">
        <a
            href="{{ url()->current() }}"
            x-bind:aria-disabled="seconds > 0"
            x-bind:class="seconds > 0 ? 'pointer-events-none opacity-50 cursor-not-allowed' : 'hover:bg-accent/90'"
            class="inline-flex items-center justify-center rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white transition-colors"
        >
            Try again
        </a>
    </div>
</x-error-page>
