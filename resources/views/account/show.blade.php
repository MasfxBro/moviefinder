<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-text">Account</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="space-y-4 rounded-2xl border border-border bg-card p-6">
                <h3 class="text-lg font-semibold text-text">Profile</h3>
                <form method="POST" action="{{ route('account.name.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <x-primary-button>Save name</x-primary-button>
                    @if (session('status') === 'name-updated')
                        <span class="text-sm text-muted">Saved.</span>
                    @endif
                </form>
                <p class="text-sm text-muted">{{ $user->email }}</p>
            </section>

            @if (session('status') === 'payment-simulated')
                <p role="status" class="rounded-lg border border-success/40 bg-success/10 p-4 text-success">Pembayaran berhasil (simulasi). Tidak ada transaksi atau biaya nyata.</p>
            @endif

            <section class="space-y-4 rounded-2xl border border-border bg-card p-6">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-semibold text-text">Subscription</h3>
                    @if ($plan !== App\Enums\Plan::Free)
                        <x-plan-badge :plan="strtoupper($plan->value)" />
                    @endif
                </div>
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div><dt class="text-sm text-muted">Plan</dt><dd class="text-text">{{ ucfirst($plan->value) }}</dd></div>
                    <div><dt class="text-sm text-muted">Billing mode</dt><dd class="text-text">{{ $subscription?->billing_mode ?? 'No active billing' }}</dd></div>
                    <div><dt class="text-sm text-muted">Renewal or paid-until date</dt><dd class="text-text">{{ $subscription?->current_period_end?->format('M j, Y') ?? 'Not applicable' }}</dd></div>
                    <div><dt class="text-sm text-muted">Smart searches left</dt><dd class="text-text">{{ $quota['smart_left'] }}</dd></div>
                    <div><dt class="text-sm text-muted">AI allowance used</dt><dd class="text-text">{{ $quota['allowance_limit'] > 0 ? (int) round($quota['allowance_used'] / $quota['allowance_limit'] * 100) : 0 }}%</dd></div>
                    <div><dt class="text-sm text-muted">AI searches left today</dt><dd class="text-text">{{ $quota['units_left'] }} of {{ $quota['units_limit'] }}</dd></div>
                </dl>
                <div class="flex flex-wrap gap-3">
                    @if ($subscription?->billing_mode === 'auto' && in_array($subscription->status->value, ['active', 'past_due'], true))
                        <form method="POST" action="{{ route('account.subscription.cancel') }}">@csrf<button class="rounded-lg border border-border px-4 py-2 text-text">Cancel renewal</button></form>
                    @elseif ($subscription?->status->value === 'canceled' && $subscription->current_period_end?->isFuture())
                        <form method="POST" action="{{ route('account.subscription.resume') }}">@csrf<button class="rounded-lg border border-border px-4 py-2 text-text">Resume</button></form>
                    @endif
                    <a href="{{ route('pricing') }}" class="rounded-lg border border-border px-4 py-2 text-text">See plans</a>
                </div>
                @if (! $user->isAdmin())
                    <div class="pt-3 border-t border-border">
                        <a href="{{ route('home', ['tour' => 1]) }}" class="text-sm font-medium text-accent hover:underline focus:outline-none focus:ring-2 focus:ring-accent rounded">
                            Take the tour again
                        </a>
                    </div>
                @endif
            </section>

            <section class="space-y-4 rounded-2xl border border-border bg-card p-6">
                @include('profile.partials.update-password-form')
            </section>

            <section class="space-y-4 rounded-2xl border border-red-900 bg-card p-6">
                <h3 class="text-lg font-semibold text-text">Delete account</h3>
                <p class="text-sm text-muted">Personal data will be deleted. Payment records are retained without account identifiers.</p>
                <form method="POST" action="{{ route('account.destroy') }}" class="space-y-4">
                    @csrf
                    @method('DELETE')
                    <div>
                        <x-input-label for="delete_password" value="Confirm password" />
                        <x-text-input id="delete_password" name="password" type="password" required autocomplete="current-password" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                    </div>
                    <x-button variant="danger" type="submit">Delete account</x-button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
