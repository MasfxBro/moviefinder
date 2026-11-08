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

            <section class="space-y-4 rounded-2xl border border-border bg-card p-6">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-semibold text-text">Subscription</h3>
                    @if ($plan === App\Enums\Plan::Pro)
                        <span class="rounded-full bg-accent px-3 py-1 text-xs font-semibold text-text">PRO</span>
                    @elseif ($plan === App\Enums\Plan::Plus)
                        <span class="rounded-full bg-accent px-3 py-1 text-xs font-semibold text-text">PLUS</span>
                    @endif
                </div>
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div><dt class="text-sm text-muted">Plan</dt><dd class="text-text">{{ ucfirst($plan->value) }}</dd></div>
                    <div><dt class="text-sm text-muted">Billing mode</dt><dd class="text-text">{{ $subscription['billing_mode'] ?? 'No active billing' }}</dd></div>
                    <div><dt class="text-sm text-muted">Renewal or paid-until date</dt><dd class="text-text">{{ $subscription['period_end'] ?? 'Not applicable' }}</dd></div>
                    <div><dt class="text-sm text-muted">Smart searches left</dt><dd class="text-text">{{ $quota['smart_left'] }}</dd></div>
                    <div><dt class="text-sm text-muted">AI allowance used</dt><dd class="text-text">{{ $quota['allowance_limit'] > 0 ? (int) round($quota['allowance_used'] / $quota['allowance_limit'] * 100) : 0 }}%</dd></div>
                    <div><dt class="text-sm text-muted">AI searches left today</dt><dd class="text-text">{{ $quota['units_left'] }} of {{ $quota['units_limit'] }}</dd></div>
                </dl>
                <div class="flex flex-wrap gap-3">
                    <x-button variant="secondary" type="button" disabled>Upgrade</x-button>
                    <x-button variant="secondary" type="button" disabled>Downgrade</x-button>
                    <x-button variant="secondary" type="button" disabled>Cancel</x-button>
                    <span class="self-center text-sm text-muted">Available when billing launches.</span>
                </div>
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
