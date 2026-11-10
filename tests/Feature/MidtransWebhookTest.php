<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Jobs\ProcessMidtransWebhook;
use App\Models\Payment;
use App\Models\User;
use App\Services\MidtransGateway;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function midtransNotification(array $overrides = []): array
{
    $payload = array_merge([
        'order_id' => 'MF-1-1730000000-test1234',
        'status_code' => '200',
        'gross_amount' => '15000.00',
        'transaction_status' => 'settlement',
        'transaction_id' => 'tx-1',
    ], $overrides);
    $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'server-test');

    return $payload;
}

beforeEach(function () {
    config(['services.midtrans.server_key' => 'server-test']);
    Http::fake([
        'api.sandbox.midtrans.com/v2/MF-1-1730000000-test1234/status' => Http::response(['order_id' => 'MF-1-1730000000-test1234', 'gross_amount' => '15000.00', 'transaction_status' => 'settlement', 'transaction_time' => '2026-11-09 12:00:00', 'fraud_status' => 'accept', 'saved_token_id' => 'secure-recurring-token'], 200),
        'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(['token' => 'snap-token', 'redirect_url' => 'https://pay.example.test/snap'], 201),
        'api.sandbox.midtrans.com/v1/subscriptions/*/disable' => Http::response([], 200),
        'api.sandbox.midtrans.com/v1/subscriptions/*/enable' => Http::response([], 200),
        'api.sandbox.midtrans.com/v2/pay/account/*' => Http::response(['metadata' => ['payment_options' => [['token' => 'wallet-token']]]]),
    ]);
    Queue::fake();
});

test('successful auto renewal creates and stores a recurring subscription token', function () {
    Http::preventStrayRequests();
    $payload = midtransNotification();
    $user = User::factory()->create();
    $subscription = $user->subscriptions()->create(['plan' => Plan::Plus, 'billing_mode' => 'auto', 'payment_method' => 'credit_card', 'status' => 'pending', 'source' => 'midtrans']);
    Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => $payload['order_id'], 'plan' => Plan::Plus, 'method' => 'credit_card', 'amount' => 15000]);
    Http::fake([
        'api.sandbox.midtrans.com/v2/MF-1-1730000000-test1234/status' => Http::response(['order_id' => $payload['order_id'], 'gross_amount' => '15000.00', 'transaction_status' => 'settlement', 'transaction_time' => '2026-11-09 12:00:00', 'fraud_status' => 'accept', 'saved_token_id' => 'secure-recurring-token'], 200),
        'api.sandbox.midtrans.com/v1/subscriptions' => Http::response(['id' => 'recurring-subscription'], 201),
    ]);

    $this->postJson('/billing/webhook', $payload)->assertOk();
    Queue::assertPushed(ProcessMidtransWebhook::class, 1);
    Queue::pushed(ProcessMidtransWebhook::class)->first()->handle(app(MidtransGateway::class), app(SubscriptionService::class));

    expect($subscription->fresh()->gateway_subscription_id)->toBe('recurring-subscription')
        ->and($subscription->fresh()->gateway_token)->toBe('secure-recurring-token')
        ->and($subscription->fresh()->status->value)->toBe('active');
});

test('recurring Midtrans notifications require a valid standard signature before queueing', function () {
    $payload = [
        'event_name' => 'subscription.charge',
        'transaction' => ['transaction_status' => 'capture', 'transaction_id' => 'charge-001'],
        'subscription' => ['id' => 'recurring-id'],
    ];

    $this->postJson('/billing/webhook', $payload)->assertUnauthorized();
    Queue::assertNothingPushed();
});

test('recurring Midtrans notifications verify and apply nested charge events', function () {
    $user = User::factory()->create();
    $subscription = $user->subscriptions()->create([
        'plan' => Plan::Plus,
        'billing_mode' => 'auto',
        'payment_method' => 'credit_card',
        'status' => 'active',
        'source' => 'midtrans',
        'gateway_subscription_id' => 'recurring-id',
        'gateway_token' => 'encrypted-token',
        'current_period_end' => now()->addDays(2),
    ]);
    Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => 'initial-order', 'plan' => Plan::Plus, 'method' => 'credit_card', 'amount' => 15000, 'status' => 'paid']);
    Http::fake(['api.sandbox.midtrans.com/v1/subscriptions/recurring-id' => Http::response([
        'id' => 'recurring-id', 'amount' => '15000', 'status' => 'active', 'transaction_ids' => ['charge-001'],
    ])]);
    $payload = midtransNotification(['order_id' => 'midtrans-recurring-charge-001', 'transaction_id' => 'charge-001', 'event_name' => 'subscription.charge', 'subscription' => ['id' => 'recurring-id'], 'transaction' => ['transaction_status' => 'capture', 'transaction_id' => 'charge-001']]);

    $this->postJson('/billing/webhook', $payload)->assertOk();
    Queue::pushed(ProcessMidtransWebhook::class)->first()->handle(app(MidtransGateway::class), app(SubscriptionService::class));

    expect($subscription->fresh()->current_period_end->toDateString())->toBe(now()->addMonth()->toDateString())
        ->and($subscription->payments()->where('gateway_order_id', 'midtrans-recurring-charge-001')->value('status')->value)->toBe('paid');
});

test('auto checkout fails closed when recurring token is missing', function () {
    $payload = midtransNotification(['order_id' => 'missing-token-order']);
    $user = User::factory()->create();
    $subscription = $user->subscriptions()->create(['plan' => Plan::Plus, 'billing_mode' => 'auto', 'payment_method' => 'credit_card', 'status' => 'pending', 'source' => 'midtrans']);
    $payment = Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => $payload['order_id'], 'plan' => Plan::Plus, 'method' => 'credit_card', 'amount' => 15000]);
    Http::fake([
        'api.sandbox.midtrans.com/v2/missing-token-order/status' => Http::response([
            'order_id' => $payload['order_id'], 'gross_amount' => '15000.00', 'transaction_status' => 'settlement', 'fraud_status' => 'accept',
        ]),
        'api.sandbox.midtrans.com/v1/subscriptions' => Http::response(['message' => 'Unauthorized'], 401),
    ]);

    $this->postJson('/billing/webhook', $payload)->assertOk();
    Queue::pushed(ProcessMidtransWebhook::class)->first()->handle(app(MidtransGateway::class), app(SubscriptionService::class));

    expect($payment->fresh()->status->value)->toBe('failed')
        ->and($subscription->fresh()->status->value)->toBe('past_due')
        ->and($subscription->fresh()->gateway_subscription_id)->toBeNull();
});

test('GoPay tokenization fetches account token before creating recurring subscription', function () {
    $payload = midtransNotification(['order_id' => 'gopay-token-order']);
    $user = User::factory()->create();
    $subscription = $user->subscriptions()->create(['plan' => Plan::Plus, 'billing_mode' => 'auto', 'payment_method' => 'gopay', 'status' => 'pending', 'source' => 'midtrans']);
    Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => $payload['order_id'], 'plan' => Plan::Plus, 'method' => 'gopay', 'amount' => 15000]);
    Http::fake([
        'api.sandbox.midtrans.com/v2/gopay-token-order/status' => Http::response(['order_id' => $payload['order_id'], 'gross_amount' => '15000.00', 'transaction_status' => 'settlement', 'fraud_status' => 'accept', 'account_id' => 'wallet-account'], 200),
        'api.sandbox.midtrans.com/v2/pay/account/wallet-account' => Http::response(['metadata' => ['payment_options' => [['token' => 'wallet-token']]]]),
        'api.sandbox.midtrans.com/v1/subscriptions' => Http::response(['id' => 'wallet-subscription'], 201),
    ]);

    $this->postJson('/billing/webhook', $payload)->assertOk();
    Queue::pushed(ProcessMidtransWebhook::class)->first()->handle(app(MidtransGateway::class), app(SubscriptionService::class));

    expect($subscription->fresh()->gateway_subscription_id)->toBe('wallet-subscription')
        ->and($subscription->fresh()->gateway_account_id)->toBe('wallet-account')
        ->and($subscription->fresh()->gateway_token)->toBe('wallet-token');
});

test('recurring failure webhook is idempotent when replayed', function () {
    $user = User::factory()->create();
    $subscription = $user->subscriptions()->create([
        'plan' => Plan::Plus, 'billing_mode' => 'auto', 'payment_method' => 'gopay', 'status' => 'active', 'source' => 'midtrans',
        'gateway_subscription_id' => 'replayed-failure-id', 'gateway_token' => 'wallet-token', 'current_period_end' => now()->addDays(2),
    ]);
    Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => 'initial-replay', 'plan' => Plan::Plus, 'method' => 'gopay', 'amount' => 15000, 'status' => 'paid']);
    Http::fake(['api.sandbox.midtrans.com/v1/subscriptions/replayed-failure-id' => Http::response([
        'id' => 'replayed-failure-id', 'amount' => '15000', 'status' => 'inactive', 'transaction_ids' => [],
    ])]);
    $payload = midtransNotification(['order_id' => 'failed-recurring-order', 'transaction_id' => 'failed-charge-1', 'transaction_status' => 'failure', 'event_name' => 'subscription.charge', 'subscription' => ['id' => 'replayed-failure-id'], 'transaction' => ['transaction_id' => 'failed-charge-1']]);

    $this->postJson('/billing/webhook', $payload)->assertOk();
    $job = Queue::pushed(ProcessMidtransWebhook::class)->first();
    $job->handle(app(MidtransGateway::class), app(SubscriptionService::class));
    $job->handle(app(MidtransGateway::class), app(SubscriptionService::class));

    expect($subscription->payments()->where('gateway_order_id', 'midtrans-recurring-failed-charge-1')->count())->toBe(1);
});

test('authenticated owner can cancel then resume their recurring subscription', function () {
    $user = User::factory()->create();
    $subscription = $user->subscriptions()->create([
        'plan' => Plan::Plus, 'billing_mode' => 'auto', 'payment_method' => 'credit_card', 'status' => 'active', 'source' => 'midtrans',
        'gateway_subscription_id' => 'owner-subscription', 'gateway_token' => 'owner-token', 'current_period_end' => now()->addDays(10),
    ]);

    $this->actingAs($user)->post('/account/subscription/cancel')->assertRedirect();
    expect($subscription->fresh()->status->value)->toBe('canceled');
    $this->actingAs($user)->post('/account/subscription/resume')->assertRedirect();
    expect($subscription->fresh()->status->value)->toBe('active');
    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/owner-subscription/disable'));
    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/owner-subscription/enable'));
});

test('webhook ignores stale status notifications', function () {
    $payload = midtransNotification(['transaction_status' => 'pending', 'transaction_time' => '2026-01-01 00:00:00']);
    $user = User::factory()->create();
    $subscription = $user->subscriptions()->create(['plan' => Plan::Plus, 'billing_mode' => 'manual', 'status' => 'pending', 'source' => 'midtrans']);
    Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => $payload['order_id'], 'plan' => Plan::Plus, 'method' => 'qris', 'amount' => 15000]);
    Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response(['order_id' => $payload['order_id'], 'gross_amount' => '15000.00', 'transaction_status' => 'settlement', 'transaction_time' => '2026-02-01 00:00:00'], 200)]);

    $this->postJson('/billing/webhook', $payload)->assertOk();
    Queue::assertPushed(ProcessMidtransWebhook::class, 1);
    $job = Queue::pushed(ProcessMidtransWebhook::class)->first();
    $job->handle(app(MidtransGateway::class), app(SubscriptionService::class));

    expect($subscription->fresh()->status->value)->toBe('pending');
    $this->assertDatabaseHas('payments', ['gateway_order_id' => $payload['order_id'], 'status' => 'pending']);
});

test('recurring webhook handles verified failed charge as past due', function () {
    $user = User::factory()->create();
    $subscription = $user->subscriptions()->create([
        'plan' => Plan::Plus, 'billing_mode' => 'auto', 'payment_method' => 'gopay', 'status' => 'active', 'source' => 'midtrans',
        'gateway_subscription_id' => 'failed-recurring-id', 'gateway_token' => 'wallet-token', 'current_period_end' => now()->addDays(2),
    ]);
    Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => 'initial-failed-test', 'plan' => Plan::Plus, 'method' => 'gopay', 'amount' => 15000, 'status' => 'paid']);
    Http::fake(['api.sandbox.midtrans.com/v1/subscriptions/failed-recurring-id' => Http::response([
        'id' => 'failed-recurring-id', 'amount' => '15000', 'status' => 'inactive', 'transaction_ids' => [],
    ])]);
    $payload = midtransNotification(['order_id' => 'failed-recurring-order-2', 'transaction_id' => 'failed-charge-2', 'transaction_status' => 'failure', 'status_code' => '411', 'event_name' => 'subscription.charge', 'subscription' => ['id' => 'failed-recurring-id'], 'transaction' => ['status_code' => '411', 'status_message' => 'Token invalid']]);

    $this->postJson('/billing/webhook', $payload)->assertOk();
    Queue::pushed(ProcessMidtransWebhook::class)->first()->handle(app(MidtransGateway::class), app(SubscriptionService::class));

    expect($subscription->fresh()->status->value)->toBe('past_due')
        ->and($subscription->payments()->where('status', 'failed')->exists())->toBeTrue();
});

test('webhook rejects a tampered signature', function () {
    $payload = midtransNotification();
    $payload['gross_amount'] = '1.00';

    $this->postJson('/billing/webhook', $payload)->assertUnauthorized();

    Queue::assertNothingPushed();
});

test('webhook accepts a valid notification and queues duplicate events once', function () {
    $payload = midtransNotification();

    $this->postJson('/billing/webhook', $payload)->assertOk()->assertJson(['ok' => true]);
    $this->postJson('/billing/webhook', $payload)->assertOk()->assertJson(['ok' => true]);

    expect(DB::table('webhook_events')->count())->toBe(1);
    Queue::assertPushed(ProcessMidtransWebhook::class, 1);
});
