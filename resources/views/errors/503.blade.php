@php
    $maintenanceMessage = isset($exception) && trim((string) $exception->getMessage()) !== ''
        ? trim($exception->getMessage())
        : 'We are performing maintenance. Please try again shortly.';
@endphp

<x-error-page
    code="503"
    title="We will be back shortly"
    :message="$maintenanceMessage"
>
    <a href="{{ url()->current() }}" class="inline-flex items-center justify-center rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent/90">
        Refresh
    </a>
</x-error-page>
