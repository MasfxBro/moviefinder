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
    <button onclick="window.location.reload()" class="inline-flex items-center justify-center px-4 py-2 bg-accent text-white font-medium text-sm rounded-lg hover:bg-accent/90 transition-colors">
        Refresh
    </button>
</x-error-page>
