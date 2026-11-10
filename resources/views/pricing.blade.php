<x-app-layout>
    <div class="mx-auto w-full max-w-7xl space-y-8 py-12">
        <header class="space-y-2 text-center">
            <h1 class="text-3xl font-bold text-text">Plans and pricing</h1>
            <p class="text-muted">The same final price for every payment method.</p>
            @if ($isDemo)
                <p class="rounded-lg border border-warning/40 bg-warning/10 p-3 text-warning">Demo only: checkout is simulated. No payment provider is contacted and no fee is charged.</p>
            @endif
            @if (session('upgrade_warning'))<p role="alert" class="rounded-lg border border-border p-3 text-text">{{ session('upgrade_warning') }}</p>@endif
        </header>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach (['free' => 'Free', 'plus' => 'Plus', 'pro' => 'Pro'] as $key => $name)
                <section class="space-y-5 rounded-2xl border border-border bg-card p-6">
                    <h2 class="text-2xl font-semibold text-text">{{ $name }}</h2>
                    <p class="text-xl text-text">Rp{{ number_format($plans[$key]['price_idr'], 0, ',', '.') }}{{ $key === 'free' ? '' : ' / month' }}</p>
                    <dl class="space-y-2 text-sm text-muted">
                        @foreach (['Daily searches' => 'daily_units', 'AI clip searches' => 'ai_clip_searches_period', 'Watchlist limit' => 'watchlist_max'] as $label => $field)
                            <div class="flex justify-between gap-2"><dt>{{ $label }}</dt><dd>{{ $plans[$key][$field] }}</dd></div>
                        @endforeach
                    </dl>
                    @if ($key !== 'free')
                        <div class="space-y-3 rounded-xl bg-accent-soft p-4">
                            <h3 class="font-semibold text-text">Auto-renew monthly</h3>
                            <p class="text-sm text-muted">Card or GoPay. Cancel anytime; access continues until period end. We will remind you before each renewal. Receipt is sent after every successful charge.</p>
                            <form method="POST" action="{{ route('billing.checkout') }}" class="space-y-3">@csrf
                                <input type="hidden" name="plan" value="{{ $key }}"><input type="hidden" name="mode" value="auto">
                                <select name="method" class="w-full rounded border-border bg-card text-text"><option value="credit_card">Card (Visa, Mastercard, JCB)</option><option value="gopay">GoPay</option></select>
                                <label class="flex gap-2 text-sm text-text"><input required type="checkbox" name="consent" value="1">I authorize a monthly automatic charge of Rp{{ number_format($plans[$key]['price_idr'], 0, ',', '.') }} until I cancel. @if ($isDemo) (simulation only; no charge) @endif</label>
                                @auth <button class="w-full rounded-lg bg-accent px-4 py-2 font-semibold text-text">Continue</button>@else<a class="block text-center underline" href="{{ route('login') }}">Sign in to continue</a>@endauth
                            </form>
                            @foreach (['OVO', 'DANA', 'ShopeePay', 'LinkAja'] as $wallet)<span class="mr-2 text-xs text-muted">{{ $wallet }} auto-debit: available at public launch</span>@endforeach
                        </div>
                        <div class="space-y-3 rounded-xl border border-border p-4">
                            <h3 class="font-semibold text-text">Pay for 30 days</h3><p class="text-sm text-muted">No automatic renewal. Each payment adds 30 days; paying before expiry extends from the current end date. We will remind you 3 days before expiry.</p><p class="text-xs text-muted">Receipt is sent after successful payment.</p>
                            <div class="flex flex-wrap gap-2 text-xs text-muted">@foreach ($methods[$key] as $method)<span class="rounded-full border border-border px-2 py-1">{{ ucfirst(str_replace('_', ' ', $method)) }}</span>@endforeach</div>
                            <form method="POST" action="{{ route('billing.checkout') }}" class="space-y-2">@csrf<input type="hidden" name="plan" value="{{ $key }}"><input type="hidden" name="mode" value="manual">@if (auth()->user()?->subscriptions()->where('status', 'active')->where('plan', 'plus')->exists() && $key === 'pro')<label class="flex gap-2 text-xs text-muted"><input required type="checkbox" name="upgrade_confirm" value="1">I understand this upgrade starts immediately and forfeits remaining days.</label>@endif<select name="method" class="w-full rounded border-border bg-card text-text">@foreach ($methods[$key] as $method)<option value="{{ $method }}">{{ ucfirst(str_replace('_', ' ', $method)) }}</option>@endforeach</select>@auth<button class="w-full rounded-lg border border-border px-4 py-2 font-semibold text-text">Continue</button>@else<a class="rounded-lg border border-border px-3 py-2 text-text" href="{{ route('login') }}">Sign in</a>@endauth</form>
                        </div>
                        <p class="text-sm text-muted">Upgrade starts now; remaining days are forfeited. Downgrade starts next renewal. No proration. <a class="underline" href="{{ route('refund') }}">Refund policy</a>.</p>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</x-guest-layout>
