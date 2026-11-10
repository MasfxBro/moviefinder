<x-error-page
    code="419"
    title="Session expired"
    message="Your session expired. Reload the page and try again."
>
    <a href="{{ url()->current() }}" class="inline-flex items-center justify-center rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent/90">
        Reload
    </a>
</x-error-page>
