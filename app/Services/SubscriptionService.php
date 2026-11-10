<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\Plan;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function start(User $user, Plan $plan, string $mode, ?string $method = null): Subscription
    {
        if (! in_array($mode, ['auto', 'manual'], true) || $plan === Plan::Free) {
            throw new InvalidArgumentException('Paid plan and valid billing mode required.');
        }

        return DB::transaction(function () use ($user, $plan, $mode, $method): Subscription {
            $current = $user->subscriptions()->where('billing_mode', $mode)->whereIn('status', ['active', 'past_due', 'pending'])->latest()->lockForUpdate()->first();
            if ($current && $current->getRawOriginal('plan') === Plan::Pro->value && $plan->value === Plan::Plus->value && in_array($current->getRawOriginal('status'), [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value], true)) {
                $current->update(['pending_plan' => $plan]);

                return $current;
            }
            if ($current && $current->getRawOriginal('status') === SubscriptionStatus::Pending->value && $current->getRawOriginal('plan') === $plan->value) {
                return $current;
            }
            if ($current && $current->billing_mode === $mode && $this->rank($plan) < $this->rank($current->getRawOriginal('plan') instanceof Plan ? $current->getRawOriginal('plan') : Plan::from($current->getRawOriginal('plan')))) {
                $current->update(['pending_plan' => $plan]);

                return $current;
            }

            return $user->subscriptions()->create([
                'plan' => $plan, 'billing_mode' => $mode, 'payment_method' => $method,
                'status' => SubscriptionStatus::Pending,
                'source' => 'midtrans', 'current_period_start' => null,
                'current_period_end' => null,
            ]);
        });
    }

    public function renew(Subscription $subscription, Payment $payment): void
    {
        DB::transaction(function () use ($subscription, $payment): void {
            $subscription->refresh();
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $plan = $subscription->pending_plan ?? $payment->plan;
            if ($payment->status !== PaymentStatus::Pending && $subscription->pending_plan === null) {
                return;
            }
            if ($subscription->billing_mode === 'auto' && $subscription->pending_plan && $subscription->gateway_subscription_id) {
                if (! $subscription->gateway_token) {
                    throw new InvalidArgumentException('Gateway token unavailable for subscription update.');
                }
                $amount = (int) config('plans.'.$plan->value.'.price_idr');
                $this->gateway->updateSubscription($subscription->gateway_subscription_id, $amount, $subscription->gateway_token, $subscription->payment_method ?? 'credit_card', $subscription->gateway_account_id);
            }
            $current = $subscription->user->subscriptions()->where('id', '!=', $subscription->id)->whereIn('status', ['active', 'past_due', 'canceled'])->lockForUpdate()->get();
            foreach ($current as $old) {
                if ($old->gateway_subscription_id) {
                    $this->gateway->cancel($old->gateway_subscription_id);
                }
                $old->update(['status' => SubscriptionStatus::Expired, 'cancel_at_period_end' => false]);
            }
            $start = $subscription->billing_mode === 'manual' && $subscription->current_period_end?->isFuture()
                ? $subscription->current_period_end
                : now();
            $subscription->update(['plan' => $plan, 'pending_plan' => null, 'status' => SubscriptionStatus::Active, 'current_period_start' => now(), 'current_period_end' => $subscription->billing_mode === 'manual' ? $start->copy()->addDays(30) : now()->addMonth(), 'cancel_at_period_end' => false, 'canceled_at' => null, 'grace_ends_at' => null]);
            $payment->update(['status' => 'paid', 'paid_at' => now(), 'subscription_id' => $subscription->id]);
        });
    }

    public function fail(Subscription $subscription): void
    {
        if ($subscription->billing_mode === 'manual') {
            return;
        }

        $subscription->update(['status' => SubscriptionStatus::PastDue, 'grace_ends_at' => now()->addDays(3)]);
    }

    public function cancel(Subscription $subscription): void
    {
        if ($subscription->billing_mode === 'manual') {
            return;
        }

        if ($subscription->gateway_subscription_id) {
            $this->gateway->cancel($subscription->gateway_subscription_id);
        }
        $subscription->update(['status' => SubscriptionStatus::Canceled, 'cancel_at_period_end' => true, 'canceled_at' => now()]);
    }

    public function resume(Subscription $subscription): void
    {
        if ($subscription->billing_mode !== 'auto' || ! $subscription->gateway_subscription_id || $subscription->current_period_end?->isPast()) {
            throw new InvalidArgumentException('Subscription cannot be resumed.');
        }

        $this->gateway->resume($subscription->gateway_subscription_id);
        $subscription->update(['status' => SubscriptionStatus::Active, 'cancel_at_period_end' => false, 'canceled_at' => null]);
    }

    public function expire(Subscription $subscription): void
    {
        $expiry = $subscription->status === SubscriptionStatus::PastDue
            ? $subscription->grace_ends_at
            : $subscription->current_period_end;
        if ($expiry?->isFuture() || $expiry === null) {
            return;
        }

        $subscription->update(['status' => SubscriptionStatus::Expired, 'cancel_at_period_end' => false]);
    }

    private function rank(Plan $plan): int
    {
        return match ($plan) {
            Plan::Free => 0, Plan::Plus => 1, Plan::Pro => 2
        };
    }
}
