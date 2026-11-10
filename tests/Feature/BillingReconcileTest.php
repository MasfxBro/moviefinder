<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\RenewalReminder;
use App\Services\Admin\AiMonitoringService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

function reconcileSubscription(User $user, string $mode, array $attributes = []): Subscription
{
    return $user->subscriptions()->create(array_merge([
        'plan' => Plan::Plus,
        'billing_mode' => $mode,
        'status' => 'active',
        'source' => 'midtrans',
        'current_period_start' => now()->subDays(27),
        'current_period_end' => now()->addDays(3),
    ], $attributes));
}

it('queues auto renewal reminder before charge and manual reminder with pay link before expiry', function (string $mode) {
    Notification::fake();
    reconcileSubscription(User::factory()->create(), $mode);
    Http::preventStrayRequests();
    Artisan::call('billing:reconcile');

    Notification::assertSentTo(User::first(), RenewalReminder::class, fn (RenewalReminder $notification): bool => $notification->subscription->billing_mode === $mode
        && ($mode === 'auto' ? $notification->paymentUrl === null : str_contains((string) $notification->paymentUrl, '/pricing?')));
})->with(['auto', 'manual']);

it('ignores reminders outside the three-day lead window', function () {
    Notification::fake();
    reconcileSubscription(User::factory()->create(), 'auto', ['current_period_end' => now()->addDays(10)]);
    Artisan::call('billing:reconcile');
    Notification::assertNothingSent();
});

it('reconciles successful payment after a missed webhook', function () {
    Http::fake(['*' => Http::response([
        'order_id' => 'reconcile-order', 'gross_amount' => '15000', 'transaction_status' => 'settlement', 'fraud_status' => 'accept',
    ])]);
    $user = User::factory()->create();
    $subscription = reconcileSubscription($user, 'manual', ['status' => 'pending', 'current_period_end' => null]);
    config(['services.midtrans.production' => false]);
    $payment = Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => 'reconcile-order', 'plan' => 'plus', 'amount' => 15000, 'status' => 'pending']);
    $payment->forceFill(['created_at' => now()->subMinutes(20)])->save();

    Artisan::call('billing:reconcile');

    expect($payment->fresh()->status->value)->toBe('paid')
        ->and($subscription->fresh()->status->value)->toBe('active');
});

it('expires a past-period subscription and reverts entitlement to Free', function () {
    $user = User::factory()->create();
    $subscription = reconcileSubscription($user, 'manual', ['current_period_end' => now()->subSecond()]);

    Artisan::call('billing:reconcile');

    expect($subscription->fresh()->status->value)->toBe('expired')
        ->and($user->fresh()->plan())->toBe(Plan::Free);
});

it('exports filtered payment CSV headers and row content', function () {
    $user = User::factory()->create();
    Payment::create(['user_id' => $user->id, 'gateway_order_id' => 'csv-order', 'plan' => 'plus', 'method' => 'qris', 'amount' => 15000, 'est_fee_idr' => 105, 'status' => 'paid', 'paid_at' => now()]);

    $admin = User::factory()->create(['role' => 'admin']);
    config(['admin.path' => 'private-ops-test']);
    $response = $this->withSession(['admin_2fa_verified' => true])->actingAs($admin, 'admin')->get(route('admin.billing.export'));

    $response->assertOk()->assertStreamedContent("order_id,email,plan,method,amount_idr,estimated_fee_idr,status,paid_at\ncsv-order,{$user->email},plus,qris,15000,105,paid,\"".now()->toDateTimeString()."\"\n");
});

it('shows per-plan profit metrics in AI monitoring', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    config(['admin.path' => 'private-ops-test']);

    $this->withSession(['admin_2fa_verified' => true])->actingAs($admin, 'admin')
        ->get(route('admin.ai-monitoring.index'))
        ->assertOk()
        ->assertSee('Estimated gateway fees')
        ->assertSee('Estimated AI cost')
        ->assertSee('Margin');
});

it('calculates profit meter totals per plan', function () {
    $user = User::factory()->create();
    $subscription = reconcileSubscription($user, 'manual');
    Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => 'profit-order', 'plan' => 'plus', 'amount' => 15000, 'est_fee_idr' => 500, 'status' => 'paid', 'paid_at' => now()]);
    DB::table('ai_usage_log')->insert(['user_id' => $user->id, 'mode' => 'global', 'model' => 'test', 'model_tier' => 'standard', 'grounded' => false, 'input_tokens' => 100, 'output_tokens' => 50, 'cost_idr' => 600, 'status' => 'ok', 'created_at' => now()]);

    expect(app(AiMonitoringService::class)->profitByPlan()['plus'])->toBe(['revenue' => 15000, 'fees' => 500, 'ai_cost' => 600, 'margin' => 13900]);
});
