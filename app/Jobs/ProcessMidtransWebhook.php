<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\MidtransGateway;
use App\Services\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcessMidtransWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $eventId, public string $orderId) {}

    public function handle(MidtransGateway $gateway, SubscriptionService $subscriptions): void
    {
        $event = DB::table('webhook_events')->where('id', $this->eventId)->first();
        if (! $event || $event->processed_at) {
            return;
        }

        $notification = json_decode($event->payload, true);
        if (($notification['event_name'] ?? null) === 'subscription.charge' || str_starts_with($this->orderId, 'midtrans-recurring-')) {
            $this->processRecurring($event, ($notification['event_name'] ?? null) === 'subscription.charge' ? $notification : [
                'subscription' => ['id' => $notification['subscription_id'] ?? ''],
                'transaction' => [
                    'transaction_status' => $notification['transaction_status'] ?? null,
                    'transaction_id' => $notification['transaction_id'] ?? null,
                    'status_code' => $notification['status_code'] ?? null,
                    'status_message' => $notification['status_message'] ?? null,
                ],
            ], $gateway, $subscriptions);

            return;
        }

        $payment = Payment::where('gateway_order_id', $this->orderId)->first();
        if (! $payment) {
            DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);

            return;
        }

        $status = $gateway->status($this->orderId);
        if ((int) (float) ($status['gross_amount'] ?? 0) !== $payment->amount || ($status['order_id'] ?? null) !== $payment->gateway_order_id) {
            DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);

            return;
        }
        $notification = json_decode($event->payload, true);
        $notificationTime = isset($notification['transaction_time']) ? strtotime($notification['transaction_time']) : false;
        $currentTime = isset($status['transaction_time']) ? strtotime($status['transaction_time']) : false;
        if ($notificationTime && $currentTime && $notificationTime < $currentTime && ($notification['transaction_status'] ?? null) !== ($status['transaction_status'] ?? null)) {
            DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);

            return;
        }
        if ($payment->getRawOriginal('status') !== PaymentStatus::Pending->value && ($notification['transaction_status'] ?? null) === ($status['transaction_status'] ?? null)) {
            DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);

            return;
        }

        if (in_array($status['transaction_status'] ?? '', ['settlement', 'capture'], true) && ($status['fraud_status'] ?? 'accept') === 'accept') {
            $subscription = Subscription::find($payment->subscription_id);
            if ($subscription) {
                if ($subscription->billing_mode === 'auto' && ! $subscription->gateway_subscription_id) {
                    $accountId = $payment->method === 'gopay' ? ($status['account_id'] ?? $status['metadata']['account_id'] ?? null) : null;
                    $token = $payment->method === 'gopay' && $accountId
                        ? data_get($gateway->gopayToken($accountId), 'metadata.payment_options.0.token')
                        : ($status['saved_token_id'] ?? $status['token_id'] ?? null);
                    if ($token && ($payment->method !== 'gopay' || $accountId)) {
                        $recurring = $gateway->createSubscription('MovieFinder-'.$subscription->id, (string) $token, $payment->amount, $payment->method, $accountId);
                        if (! is_string($recurring['id'] ?? null)) {
                            throw new RuntimeException('Midtrans did not return a subscription ID.');
                        }
                        $subscription->update(['gateway_subscription_id' => $recurring['id'], 'gateway_token' => $token, 'gateway_account_id' => $accountId]);
                    } else {
                        $payment->update(['status' => PaymentStatus::Failed, 'failure_reason' => 'Recurring token unavailable']);
                        $subscriptions->fail($subscription);
                        DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);

                        return;
                    }
                }
                $subscriptions->renew($subscription, $payment);
            }
        } elseif (in_array($status['transaction_status'] ?? '', ['deny', 'cancel', 'expire'], true)) {
            $payment->update(['status' => PaymentStatus::Failed, 'failure_reason' => $status['transaction_status']]);
            if ($subscription = Subscription::find($payment->subscription_id)) {
                $subscriptions->fail($subscription);
            }
        }

        DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);
    }

    /** @param object $event @param array<string, mixed> $notification */
    private function processRecurring(object $event, array $notification, MidtransGateway $gateway, SubscriptionService $subscriptions): void
    {
        $subscriptionData = $notification['subscription'] ?? [];
        $transaction = $notification['transaction'] ?? [];
        $gatewayId = $subscriptionData['id'] ?? ($this->orderId !== '' ? $this->orderId : null);
        $transactionId = $transaction['transaction_id'] ?? null;
        if (! is_string($gatewayId)) {
            DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);

            return;
        }

        $subscription = Subscription::query()->where('gateway_subscription_id', $gatewayId)->first();
        if (! $subscription) {
            DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);

            return;
        }

        $verified = $gateway->subscription($gatewayId);
        $expectedAmount = (int) config('plans.'.($subscription->pending_plan ?? $subscription->plan)->value.'.price_idr');
        $verifiedAmountMatches = (int) ($verified['amount'] ?? 0) === $expectedAmount
            || ($subscription->pending_plan !== null && (int) ($verified['amount'] ?? 0) === (int) config('plans.'.$subscription->plan->value.'.price_idr'));
        if (($verified['id'] ?? null) !== $gatewayId || ! $verifiedAmountMatches) {
            DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);

            return;
        }
        $isVerifiedSuccess = is_string($transactionId) && in_array($transactionId, $verified['transaction_ids'] ?? [], true);
        $isVerifiedFailure = ($verified['status'] ?? null) === 'inactive';
        $transactionStatus = $transaction['transaction_status'] ?? null;
        $statusCode = (string) ($transaction['status_code'] ?? '');
        if ($isVerifiedSuccess && ! in_array($transactionStatus, ['capture', 'settlement'], true)) {
            $isVerifiedSuccess = false;
        }
        if ($isVerifiedFailure && ($statusCode === '500' || $transactionStatus !== null)) {
            $isVerifiedFailure = false;
        }
        if ($isVerifiedSuccess && $subscription->pending_plan && $subscription->gateway_token) {
            $gateway->updateSubscription($gatewayId, (int) config('plans.'.$subscription->pending_plan->value.'.price_idr'), $subscription->gateway_token, $subscription->payment_method ?? 'credit_card', $subscription->gateway_account_id);
        }
        if (! $isVerifiedSuccess && ! $isVerifiedFailure) {
            DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);

            return;
        }
        if ($isVerifiedSuccess && ! is_string($transactionId)) {
            DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);

            return;
        }

        $orderId = 'midtrans-recurring-'.($transactionId ?? 'event-'.$event->id);
        $payment = Payment::query()->firstOrCreate(
            ['gateway_order_id' => $orderId],
            [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'plan' => $subscription->plan,
                'method' => $subscription->payment_method,
                'amount' => (int) $verified['amount'],
            ],
        );

        if ($isVerifiedSuccess) {
            $subscriptions->renew($subscription, $payment);
        } elseif ($isVerifiedFailure) {
            $payment->update(['status' => PaymentStatus::Failed, 'failure_reason' => $transaction['status_message'] ?? $transaction['status_code'] ?? 'Recurring charge failed']);
            $subscriptions->fail($subscription);
        }

        DB::table('webhook_events')->where('id', $event->id)->update(['processed_at' => now()]);
    }
}
