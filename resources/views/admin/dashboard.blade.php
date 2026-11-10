@extends('admin.layout')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-border">
        <div>
            <h1 class="text-2xl font-bold text-text">Admin dashboard</h1>
            <p class="text-sm text-muted">Platform metrics and operational health overview</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.titles.index') }}" class="px-4 py-2 bg-accent text-white font-medium text-sm rounded-xl hover:bg-accent/90 transition-colors">Manage Titles</a>
        </div>
    </div>

    {{-- Metrics Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Users Card --}}
        <div class="p-5 rounded-2xl bg-card border border-border flex flex-col justify-between">
            <span class="text-xs uppercase font-semibold tracking-wider text-muted">Users</span>
            <div class="mt-2">
                <span class="text-3xl font-extrabold text-text">{{ number_format($totalUsers) }}</span>
                <span class="text-xs text-muted block mt-1">+{{ number_format($newUsers7Days) }} in last 7 days</span>
            </div>
        </div>

        {{-- Paid Users by Plan --}}
        <div class="p-5 rounded-2xl bg-card border border-border flex flex-col justify-between">
            <span class="text-xs uppercase font-semibold tracking-wider text-muted">Paid Subscriptions</span>
            <div class="mt-2 flex items-baseline gap-4">
                <div>
                    <span class="text-2xl font-bold text-text">{{ $paidUsers['plus'] }}</span>
                    <span class="text-xs text-accent block">Plus</span>
                </div>
                <div>
                    <span class="text-2xl font-bold text-pro">{{ $paidUsers['pro'] }}</span>
                    <span class="text-xs text-pro block">Pro</span>
                </div>
            </div>
        </div>

        {{-- Profit Meter (Margin) --}}
        <div class="p-5 rounded-2xl bg-card border border-border flex flex-col justify-between">
            <span class="text-xs uppercase font-semibold tracking-wider text-muted">Margin (Profit Meter)</span>
            <div class="mt-2">
                <span class="text-3xl font-extrabold {{ $profitMargin >= 0 ? 'text-success' : 'text-danger' }}">
                    IDR {{ number_format($profitMargin) }}
                </span>
                <span class="text-xs text-muted block mt-1">Rev: IDR {{ number_format($revenueThisMonth) }}</span>
            </div>
        </div>

        {{-- AI Cost & Fees --}}
        <div class="p-5 rounded-2xl bg-card border border-border flex flex-col justify-between">
            <span class="text-xs uppercase font-semibold tracking-wider text-muted">Est. Costs & Fees</span>
            <div class="mt-2">
                <div class="text-sm font-semibold text-text">AI: IDR {{ number_format($aiCostThisMonth) }}</div>
                <div class="text-xs text-muted mt-0.5">Gateway: IDR {{ number_format($gatewayFeesThisMonth) }}</div>
            </div>
        </div>
    </div>

    <section class="p-5 rounded-2xl bg-card border border-border"><h2 class="text-sm uppercase font-semibold tracking-wider text-muted mb-3">Profit by plan (month to date)</h2><div class="grid grid-cols-1 sm:grid-cols-2 gap-4">@foreach ($profitByPlan as $plan => $totals)<div class="p-4 rounded-xl bg-surface"><h3 class="font-semibold capitalize">{{ $plan }}</h3><p class="text-xs text-muted">Revenue IDR {{ number_format($totals['revenue']) }} · Fees IDR {{ number_format($totals['fees']) }} · AI IDR {{ number_format($totals['ai_cost']) }}</p><p class="font-bold {{ $totals['margin'] >= 0 ? 'text-success' : 'text-danger' }}">Margin IDR {{ number_format($totals['margin']) }}</p></div>@endforeach</div></section>

    {{-- Secondary Metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- AI Quota & Budget --}}
        <div class="p-5 rounded-2xl bg-card border border-border space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs uppercase font-semibold tracking-wider text-muted">AI Usage Today</span>
                <span class="text-xs font-mono text-muted">{{ $aiUsageToday }} / {{ $globalDailyCap }}</span>
            </div>
            <div class="w-full bg-elevated rounded-full h-2.5 overflow-hidden">
                @php
                    $aiPct = $globalDailyCap > 0 ? min(100, round(($aiUsageToday / $globalDailyCap) * 100)) : 0;
                @endphp
                <div class="bg-accent h-2.5 rounded-full transition-all" style="width: {{ $aiPct }}%"></div>
            </div>
            <div class="text-xs text-muted flex justify-between pt-1">
                <span>Clip resolver budget:</span>
                <span class="font-medium text-text">IDR {{ number_format($clipResolverBudget) }}</span>
            </div>
        </div>

        {{-- Pending Queues --}}
        <div class="p-5 rounded-2xl bg-card border border-border space-y-2">
            <span class="text-xs uppercase font-semibold tracking-wider text-muted block">Pending Queues</span>
            <div class="grid grid-cols-3 gap-2 pt-1 text-center">
                <div class="p-2 rounded-xl bg-surface border border-border">
                    <span class="text-xl font-bold text-text block">{{ $pendingRequests }}</span>
                    <span class="text-[11px] text-muted">Requests</span>
                </div>
                <div class="p-2 rounded-xl bg-surface border border-border">
                    <span class="text-xl font-bold {{ $pendingReports > 0 ? 'text-danger' : 'text-text' }} block">{{ $pendingReports }}</span>
                    <span class="text-[11px] text-muted">Reports</span>
                </div>
                <div class="p-2 rounded-xl bg-surface border border-border">
                    <span class="text-xl font-bold text-text block">{{ $pendingFeedback }}</span>
                    <span class="text-[11px] text-muted">Feedback</span>
                </div>
            </div>
        </div>

        {{-- System Health Summary --}}
        <div class="p-5 rounded-2xl bg-card border border-border space-y-2">
            <span class="text-xs uppercase font-semibold tracking-wider text-muted block">System Health</span>
            <div class="space-y-1.5 pt-1 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-muted text-xs">Database:</span>
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $systemHealth['database'] ? 'text-success' : 'text-danger' }}">
                        <span class="w-2 h-2 rounded-full {{ $systemHealth['database'] ? 'bg-success' : 'bg-danger' }}"></span>
                        {{ $systemHealth['database'] ? 'Connected' : 'Unavailable' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-muted text-xs">App Version:</span>
                    <span class="text-xs font-mono text-text">{{ $systemHealth['app_version'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Missed Searches --}}
    <div class="p-5 rounded-2xl bg-card border border-border">
        <h2 class="text-sm uppercase font-semibold tracking-wider text-muted mb-3">Top Missed Searches</h2>
        @if ($topMissedSearches->isEmpty())
            <p class="text-sm text-muted py-2">No open missed searches recorded.</p>
        @else
            <div class="divide-y divide-border">
                @foreach ($topMissedSearches as $miss)
                    <div class="py-2.5 flex items-center justify-between text-sm">
                        <span class="font-medium text-text">{{ $miss->query_norm }}</span>
                        <span class="px-2 py-0.5 rounded text-xs font-mono bg-elevated text-muted">{{ $miss->hits }} hits</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
