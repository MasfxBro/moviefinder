<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Settings;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    config(['app.launch_mode' => 'demo']);
    Cache::flush();
});

// ---------------------------------------------------------------------------
// Pay for 30 days (mode=manual) — the bug that was fixed
// ---------------------------------------------------------------------------

it('redirects Pay for 30 days (Plus qris) to demo checkout', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'plus',
        'mode' => 'manual',
        'method' => 'qris',
    ]);

    // Dump the redirect target to understand where it goes
    $location = $response->headers->get('Location');
    $response->assertRedirectContains('billing/demo');

    $payment = $user->payments()->latest()->first();
    expect($payment)->not->toBeNull();

    $this->get(route('billing.demo.checkout', $payment))
        ->assertOk()
        ->assertSee('Simulation only. No real payment or charge is made.');
});

it('redirects Pay for 30 days (Plus gopay) to demo checkout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'plus',
        'mode' => 'manual',
        'method' => 'gopay',
    ])->assertRedirect();

    expect($user->payments()->latest()->first())->not->toBeNull();
});

it('redirects Pay for 30 days (Plus shopeepay) to demo checkout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'plus',
        'mode' => 'manual',
        'method' => 'shopeepay',
    ])->assertRedirect();

    expect($user->payments()->latest()->first())->not->toBeNull();
});

it('redirects Pay for 30 days (Plus credit_card) to demo checkout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'plus',
        'mode' => 'manual',
        'method' => 'credit_card',
    ])->assertRedirect();

    expect($user->payments()->latest()->first())->not->toBeNull();
});

it('redirects Pay for 30 days (Pro bank_transfer) to demo checkout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'pro',
        'mode' => 'manual',
        'method' => 'bank_transfer',
    ])->assertRedirect();

    expect($user->payments()->latest()->first())->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Auto-renew still works
// ---------------------------------------------------------------------------

it('redirects Auto-renew (credit_card) to demo checkout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'plus',
        'mode' => 'auto',
        'method' => 'credit_card',
        'consent' => '1',
    ])->assertRedirect();

    expect($user->payments()->latest()->first())->not->toBeNull();
});

it('redirects Auto-renew (gopay) to demo checkout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'plus',
        'mode' => 'auto',
        'method' => 'gopay',
        'consent' => '1',
    ])->assertRedirect();

    expect($user->payments()->latest()->first())->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Invalid method is rejected with 422
// ---------------------------------------------------------------------------

it('rejects an entirely invalid payment method with 422', function (): void {
    $user = User::factory()->create();

    // mode=auto triggers consent validation first; use manual to test method validation
    $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'plus',
        'mode' => 'manual',
        'method' => 'nonexistent_method',
    ])->assertStatus(422);

    expect($user->payments()->count())->toBe(0);
});

it('rejects bank_transfer for Plus plan (not in Plus manual list) with 422', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'plus',
        'mode' => 'manual',
        'method' => 'bank_transfer',
    ])->assertStatus(422);

    expect($user->payments()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Settings::get() returning null for the methods key does not block checkout
// ---------------------------------------------------------------------------

it('falls back to config when Settings returns null for the billing methods key', function (): void {
    $user = User::factory()->create();

    $real = app(Settings::class);
    $mock = Mockery::mock(Settings::class);
    $mock->shouldReceive('get')
        ->andReturnUsing(function (string $key, mixed $default = null) use ($real): mixed {
            if (str_starts_with($key, 'billing.methods')) {
                return null;
            }

            return $real->get($key, $default);
        });
    app()->instance(Settings::class, $mock);

    $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'plus',
        'mode' => 'manual',
        'method' => 'qris',
    ])->assertRedirect();

    expect($user->payments()->latest()->first())->not->toBeNull();
});

it('falls back to config when Settings returns a non-array for the billing methods key', function (): void {
    $user = User::factory()->create();

    $real = app(Settings::class);
    $mock = Mockery::mock(Settings::class);
    $mock->shouldReceive('get')
        ->andReturnUsing(function (string $key, mixed $default = null) use ($real): mixed {
            if (str_starts_with($key, 'billing.methods')) {
                return 'malformed-string';
            }

            return $real->get($key, $default);
        });
    app()->instance(Settings::class, $mock);

    $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'plus',
        'mode' => 'manual',
        'method' => 'qris',
    ])->assertRedirect();

    expect($user->payments()->latest()->first())->not->toBeNull();
});
