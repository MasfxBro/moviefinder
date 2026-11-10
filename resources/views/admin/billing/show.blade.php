@extends('admin.layout')

@section('content')
    <div class="space-y-6">
        <a href="{{ route('admin.billing.index') }}">Back to billing</a>
        <h1 class="text-2xl font-bold">Subscription #{{ $subscription->id }}</h1>
        <p>{{ $subscription->user?->email }} · {{ $subscription->plan->value }} · {{ $subscription->status->value }}</p>

        @if ($subscription->status->value !== 'expired')
            <form method="POST" action="{{ route('admin.billing.cancel', $subscription) }}">
                @csrf
                <button class="rounded bg-danger px-3 py-2 text-white">Cancel subscription</button>
            </form>
        @endif

        <h2 class="text-lg font-semibold">Timeline</h2>
        <ol class="space-y-3">
            @foreach ($subscription->payments as $payment)
                <li class="rounded bg-card p-4">{{ $payment->created_at->toDateTimeString() }} — Payment {{ $payment->status->value }}: IDR {{ number_format($payment->amount) }} ({{ $payment->gateway_order_id }})</li>
            @endforeach
            <li class="rounded bg-card p-4">{{ $subscription->created_at->toDateTimeString() }} — Subscription created, {{ $subscription->billing_mode }} billing.</li>
        </ol>
    </div>
@endsection
