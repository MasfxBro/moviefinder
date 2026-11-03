<x-error-page
    code="419"
    title="Session expired"
    message="Your session expired. Reload the page and try again."
>
    <button onclick="window.location.reload()" class="inline-flex items-center justify-center px-4 py-2 bg-accent text-white font-medium text-sm rounded-lg hover:bg-accent/90 transition-colors">
        Reload
    </button>
</x-error-page>
