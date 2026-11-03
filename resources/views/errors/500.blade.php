@php
    $requestId = $requestId ?? (app()->bound('request_id') ? app('request_id') : \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8)));
@endphp

<x-error-page
    code="500"
    title="Something went wrong"
    :message="'An error occurred on our side. Please try again in a moment. Reference: ' . $requestId"
>
    <button onclick="window.location.reload()" class="inline-flex items-center justify-center px-4 py-2 bg-accent text-white font-medium text-sm rounded-lg hover:bg-accent/90 transition-colors">
        Try again
    </button>
    <a href="/" class="inline-flex items-center justify-center px-4 py-2 bg-elevated text-text font-medium text-sm rounded-lg border border-border hover:bg-elevated/80 transition-colors">
        Go home
    </a>
</x-error-page>
