<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-text">Demo checkout</h2>
    </x-slot>

    <div class="mx-auto w-full max-w-xl space-y-6 rounded-2xl border border-border bg-card p-6">
        <p class="rounded-lg border border-warning/40 bg-warning/10 p-3 text-warning" role="status">Simulation only. No real payment or charge is made.</p>
        <div class="space-y-2 text-text">
            <p>Plan: {{ ucfirst($payment->plan->value) }}</p>
            <p>Method: {{ ucfirst(str_replace('_', ' ', $payment->method)) }}</p>
            <p>Simulated amount: Rp{{ number_format($payment->amount, 0, ',', '.') }}</p>
        </div>
        <form method="POST" action="{{ route('billing.demo.complete', $payment) }}">
            @csrf
            <button type="submit" class="min-h-[44px] w-full rounded-lg bg-accent px-4 py-2 font-semibold text-text">Simulate successful payment</button>
        </form>
        <a href="{{ route('pricing') }}" class="block text-center text-muted underline">Back to plans</a>
    </div>
</x-app-layout>
