<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Plan;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\FakePaymentGateway;
use App\Services\SubscriptionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\Rule;
use RuntimeException;

#[Signature('billing:simulate {user} {action : renew|fail|cancel|expire} {--mode=manual} {--plan=plus}')]
#[Description('Simulate billing transitions outside production.')]
class SimulateBilling extends Command
{
    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Billing simulation is disabled in production.');

            return self::FAILURE;
        }

        $validator = validator([
            'user' => $this->argument('user'),
            'action' => $this->argument('action'),
            'mode' => $this->option('mode'),
            'plan' => $this->option('plan'),
        ], [
            'user' => ['required', 'integer', 'exists:users,id'],
            'action' => ['required', Rule::in(['renew', 'fail', 'cancel', 'expire'])],
            'mode' => ['required', Rule::in(['auto', 'manual'])],
            'plan' => ['required', Rule::in(['plus', 'pro'])],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = User::findOrFail($this->argument('user'));
        $service = new SubscriptionService(new FakePaymentGateway);
        $action = $this->argument('action');
        $subscription = Subscription::query()->where('user_id', $user->id)->where('billing_mode', $this->option('mode'))->latest()->first();
        if ($action === 'renew') {
            if (! $subscription) {
                $subscription = $service->start($user, Plan::from($this->option('plan')), $this->option('mode'));
            }
            if ($subscription->getRawOriginal('status') === SubscriptionStatus::Expired->value) {
                $subscription = $service->start($user, Plan::from($this->option('plan')), $this->option('mode'));
            }
            $payment = Payment::create(['user_id' => $user->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => 'sim-'.bin2hex(random_bytes(12)), 'plan' => Plan::from($this->option('plan')), 'amount' => (int) config('plans.'.$this->option('plan').'.price_idr', 0), 'status' => 'pending']);
            if (! (new FakePaymentGateway)->charge($payment)) {
                return self::FAILURE;
            }
            $service->renew($subscription, $payment);
        } elseif (! $subscription) {
            throw new RuntimeException('No subscription found for user.');
        } elseif ($action === 'fail') {
            $service->fail($subscription);
        } elseif ($action === 'cancel') {
            $service->cancel($subscription);
        } elseif ($action === 'expire') {
            $service->expire($subscription);
        } else {
            $this->error('Action must be renew, fail, cancel, or expire.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
