<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Subscription;
use App\Notifications\RenewalReminder;
use App\Services\MidtransGateway;
use App\Services\Settings;
use App\Services\SubscriptionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('billing:reconcile')]
#[Description('Reconcile subscriptions, missed payment notifications, reminders, and expiry.')]
class ReconcileBilling extends Command
{
    public function handle(MidtransGateway $gateway, SubscriptionService $subscriptions, Settings $settings): int
    {
        Payment::query()->where('status', 'pending')->where('created_at', '<=', now()->subMinutes(15))
            ->where('gateway_order_id', 'not like', 'sim-%')->orderBy('id')->chunkById(100, function ($payments) use ($gateway, $subscriptions): void {
                foreach ($payments as $payment) {
                    try {
                        $status = $gateway->status($payment->gateway_order_id);
                        if (in_array($status['transaction_status'] ?? '', ['settlement', 'capture'], true) && ($status['fraud_status'] ?? 'accept') === 'accept') {
                            if ($subscription = Subscription::find($payment->subscription_id)) {
                                if ($subscription->billing_mode === 'auto' && ! $subscription->gateway_subscription_id) {
                                    continue;
                                }
                                $subscriptions->renew($subscription, $payment);
                            }
                        } elseif (in_array($status['transaction_status'] ?? '', ['deny', 'cancel', 'expire'], true)) {
                            $payment->update(['status' => 'failed', 'failure_reason' => $status['transaction_status']]);
                            if ($subscription = Subscription::find($payment->subscription_id)) {
                                $subscriptions->fail($subscription);
                            }
                        }
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }
            });

        Subscription::query()->whereIn('status', ['active', 'past_due', 'canceled'])
            ->where(function ($query): void {
                $query->where(function ($q): void {
                    $q->where('status', 'past_due')->where('grace_ends_at', '<=', now());
                })->orWhere(function ($q): void {
                    $q->whereIn('status', ['active', 'canceled'])->where('current_period_end', '<=', now());
                });
            })->orderBy('id')->chunkById(100, function ($subscriptionsChunk) use ($subscriptions): void {
                foreach ($subscriptionsChunk as $subscription) {
                    $subscriptions->expire($subscription);
                }
            });

        $now = now();
        Subscription::query()->whereIn('status', ['active', 'canceled'])->where('current_period_end', '>', $now)
            ->whereBetween('current_period_end', [$now->copy()->addDays(2)->endOfDay(), $now->copy()->addDays(3)->endOfDay()])
            ->whereNull('reminder_sent_at')->orderBy('id')->chunkById(100, function ($items): void {
                foreach ($items as $subscription) {
                    $url = null;
                    if ($subscription->billing_mode === 'manual') {
                        $url = route('pricing', ['plan' => $subscription->plan->value, 'mode' => 'manual']);
                    }
                    DB::transaction(function () use ($subscription, $url): void {
                        $locked = Subscription::query()->whereKey($subscription->id)->lockForUpdate()->first();
                        if (! $locked || $locked->reminder_sent_at) {
                            return;
                        }
                        $locked->update(['reminder_sent_at' => now()]);
                        $locked->user->notify(new RenewalReminder($locked, $url));
                    });
                }
            });

        return self::SUCCESS;
    }
}
