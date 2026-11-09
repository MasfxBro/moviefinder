<?php

declare(strict_types=1);

use App\Contracts\PaymentGateway;
use App\Enums\Plan;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\EntitlementService;
use App\Services\SubscriptionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

function billingSubscription(User $user, string $mode, Plan $plan = Plan::Plus, array $attributes = []): Subscription
{
    return $user->subscriptions()->create(array_merge([
        'plan' => $plan,
        'billing_mode' => $mode,
        'status' => SubscriptionStatus::Active,
        'source' => 'midtrans',
        'current_period_start' => now(),
        'current_period_end' => now()->addDays(30),
    ], $attributes));
}

function billingPayment(User $user, Plan $plan = Plan::Plus, ?Subscription $subscription = null): Payment
{
    return $user->payments()->create([
        'subscription_id' => $subscription?->id,
        'gateway_order_id' => 'test-'.fake()->uuid(),
        'plan' => $plan,
        'amount' => $plan === Plan::Pro ? 28000 : 15000,
    ]);
}

it('does not mutate a paid subscription when a higher plan checkout starts', function () {
    $user = User::factory()->create();
    $old = billingSubscription($user, 'auto', Plan::Plus, ['gateway_subscription_id' => 'keep-active']);
    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldNotReceive('cancel');
    app()->instance(PaymentGateway::class, $gateway);

    $new = app(SubscriptionService::class)->start($user, Plan::Pro, 'auto');

    expect($old->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($old->fresh()->gateway_subscription_id)->toBe('keep-active')
        ->and($new->status)->toBe(SubscriptionStatus::Pending);
});

it('starts both billing modes in pending status before payment', function (string $mode) {
    $user = User::factory()->create();

    $subscription = app(SubscriptionService::class)->start($user, Plan::Plus, $mode);

    expect($subscription->status)->toBe(SubscriptionStatus::Pending)
        ->and($subscription->current_period_end)->toBeNull();
})->with(['auto', 'manual']);

it('applies renewal for auto and manual subscriptions', function (string $mode) {
    $user = User::factory()->create();
    $subscription = billingSubscription($user, $mode);
    $payment = billingPayment($user, Plan::Plus, $subscription);

    app(SubscriptionService::class)->renew($subscription, $payment);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->fresh()->current_period_end->greaterThan(now()->addDays(29)))->toBeTrue()
        ->and($payment->fresh()->status->value)->toBe('paid');
})->with(['auto', 'manual']);

it('stacks manual renewals from existing end date', function () {
    $user = User::factory()->create();
    $end = now()->addDays(10);
    $subscription = billingSubscription($user, 'manual', attributes: ['current_period_end' => $end]);
    $service = app(SubscriptionService::class);

    $service->renew($subscription, billingPayment($user, Plan::Plus, $subscription));
    $firstEnd = $subscription->fresh()->current_period_end;
    $service->renew($subscription, billingPayment($user, Plan::Plus, $subscription));

    expect($subscription->fresh()->current_period_end->equalTo($firstEnd->copy()->addDays(30)))->toBeTrue();
});

it('grants three days grace, recovers on payment, then expires after grace', function () {
    $user = User::factory()->create();
    $subscription = billingSubscription($user, 'auto');
    $service = app(SubscriptionService::class);

    $service->fail($subscription);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::PastDue)
        ->and($subscription->fresh()->grace_ends_at->diffInSeconds(now()->addDays(3)))->toBeLessThan(2);
    $service->renew($subscription, billingPayment($user, Plan::Plus, $subscription));
    expect($subscription->fresh()->grace_ends_at)->toBeNull();

    $service->fail($subscription);
    Carbon::setTestNow(now()->addDays(4));
    $service->expire($subscription);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired);
    Carbon::setTestNow();
});

it('cancels auto at period end while preserving entitlement until expiry', function () {
    $user = User::factory()->create();
    $subscription = billingSubscription($user, 'auto', attributes: ['gateway_subscription_id' => 'gateway-id']);
    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('cancel')->once()->with('gateway-id');
    app()->instance(PaymentGateway::class, $gateway);

    app(SubscriptionService::class)->cancel($subscription);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Canceled)
        ->and($user->fresh()->plan())->toBe(Plan::Plus);
    Carbon::setTestNow(now()->addDays(31));
    expect($user->fresh()->plan())->toBe(Plan::Free);
    Carbon::setTestNow();
});

it('expires active auto subscriptions at the period end', function () {
    $user = User::factory()->create();
    $subscription = billingSubscription($user, 'auto', attributes: ['current_period_end' => now()->subSecond()]);

    app(SubscriptionService::class)->expire($subscription);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired);
});

it('expires manual subscriptions only after paid-through date', function () {
    $user = User::factory()->create();
    $subscription = billingSubscription($user, 'manual');
    $service = app(SubscriptionService::class);

    $service->expire($subscription);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);

    Carbon::setTestNow(now()->addDays(31));
    $service->expire($subscription);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired);
    Carbon::setTestNow();
});

it('does not apply auto failure or cancellation transitions to manual subscriptions', function (string $action) {
    $user = User::factory()->create();
    $subscription = billingSubscription($user, 'manual');
    app(SubscriptionService::class)->{$action}($subscription);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->fresh()->cancel_at_period_end)->toBeFalse();
})->with(['fail', 'cancel']);

it('does not expire a canceled auto subscription before its paid period ends', function () {
    $user = User::factory()->create();
    $subscription = billingSubscription($user, 'auto', attributes: ['status' => SubscriptionStatus::Canceled, 'cancel_at_period_end' => true]);

    app(SubscriptionService::class)->expire($subscription);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Canceled);

    Carbon::setTestNow(now()->addDays(31));
    app(SubscriptionService::class)->expire($subscription);
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired);
    Carbon::setTestNow();
});

it('keeps downgrades pending until renewal', function () {
    $user = User::factory()->create();
    $subscription = billingSubscription($user, 'auto', Plan::Pro);

    app(SubscriptionService::class)->start($user, Plan::Plus, 'auto');
    expect($subscription->fresh()->pending_plan)->toBe(Plan::Plus);

    app(SubscriptionService::class)->renew($subscription, billingPayment($user, Plan::Pro, $subscription));
    expect($subscription->fresh()->plan)->toBe(Plan::Plus)
        ->and($subscription->fresh()->pending_plan)->toBeNull();
});

it('activates paid upgrades only after successful payment and stops old subscription', function () {
    $user = User::factory()->create();
    $old = billingSubscription($user, 'auto', Plan::Plus, ['gateway_subscription_id' => 'old-id']);
    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('cancel')->once()->with('old-id');
    app()->instance(PaymentGateway::class, $gateway);

    $new = app(SubscriptionService::class)->start($user, Plan::Pro, 'auto');
    expect($old->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($new->status)->toBe(SubscriptionStatus::Pending)
        ->and($user->fresh()->plan())->toBe(Plan::Plus);

    app(SubscriptionService::class)->renew($new, billingPayment($user, Plan::Pro, $new));
    expect($old->fresh()->status)->toBe(SubscriptionStatus::Expired)
        ->and($user->fresh()->plan())->toBe(Plan::Pro);
});

it('chooses the highest unexpired entitlement and honors manual grant expiry', function () {
    $user = User::factory()->create();
    billingSubscription($user, 'manual', Plan::Plus, ['source' => 'manual']);
    billingSubscription($user, 'manual', Plan::Pro, ['source' => 'manual']);
    expect(app(EntitlementService::class)->plan($user))->toBe(Plan::Pro);

    $user->subscriptions()->update(['current_period_end' => now()->subSecond()]);
    app(EntitlementService::class)->grant($user, Plan::Plus, 'Support credit', now()->addDay());
    expect($user->fresh()->plan())->toBe(Plan::Plus);
    $user->subscriptions()->where('manual_reason', 'Support credit')->update(['current_period_end' => now()->subSecond()]);
    expect($user->fresh()->plan())->toBe(Plan::Free);
});

it('blocks billing simulation in production', function () {
    $this->app['env'] = 'production';

    $result = Artisan::call('billing:simulate', ['user' => 1, 'action' => 'renew']);

    expect($result)->toBe(1);
});
