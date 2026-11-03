@props([
    'retry' => null,
    'message' => 'The server could not be reached. Please check your connection and try again.',
])

<div
    {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 rounded-lg border border-border bg-surface px-3 py-2 text-sm text-muted']) }}
    role="alert"
>
    <span>{{ $message }}</span>
    <button
        type="button"
        @if ($retry) x-on:click="{{ $retry }}" @endif
        class="shrink-0 self-start sm:self-auto rounded-md border border-border bg-elevated px-3 py-1 text-xs font-medium text-text hover:bg-elevated/80"
    >
        Retry
    </button>
</div>
