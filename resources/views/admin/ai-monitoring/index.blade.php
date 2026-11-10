@extends('admin.layout')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-text">AI monitoring</h1>
        <p class="text-sm text-muted">Month-to-date estimated revenue, gateway fees, AI cost and margin by subscription plan.</p>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($profitByPlan as $plan => $totals)
            <section class="space-y-3 rounded-2xl border border-border bg-card p-5" aria-labelledby="plan-{{ $plan }}">
                <h2 id="plan-{{ $plan }}" class="text-lg font-semibold capitalize text-text">{{ $plan }}</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-muted">Revenue</dt><dd class="font-medium text-text">IDR {{ number_format($totals['revenue']) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Estimated gateway fees</dt><dd class="font-medium text-text">IDR {{ number_format($totals['fees']) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Estimated AI cost</dt><dd class="font-medium text-text">IDR {{ number_format($totals['ai_cost']) }}</dd></div>
                    <div class="flex justify-between gap-4 border-t border-border pt-2"><dt class="font-semibold text-text">Margin</dt><dd class="font-bold {{ $totals['margin'] >= 0 ? 'text-success' : 'text-danger' }}">IDR {{ number_format($totals['margin']) }}</dd></div>
                </dl>
            </section>
        @endforeach
    </div>
</div>
@endsection
