<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-card text-text border border-border rounded-xl font-medium text-sm hover:bg-elevated focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-bg disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
