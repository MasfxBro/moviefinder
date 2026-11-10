<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Plan;
use App\Jobs\ProcessMidtransWebhook;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\MidtransGateway;
use App\Services\Settings;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function pricing(Settings $settings): View
    {
        $plans = collect(['free', 'plus', 'pro'])->mapWithKeys(function (string $plan) use ($settings): array {
            $values = config("plans.$plan");
            foreach ($values as $key => $default) {
                $values[$key] = $settings->get("plan.$plan.$key", $default);
            }

            return [$plan => $values];
        });

        return view('pricing', [
            'plans' => $plans,
            'methods' => [
                'plus' => $settings->get('billing.methods.plus.manual', config('billing.methods.plus.manual')),
                'pro' => $settings->get('billing.methods.pro.manual', config('billing.methods.pro.manual')),
            ],
            'isDemo' => config('app.launch_mode') === 'demo',
        ]);
    }

    public function checkout(Request $request, Settings $settings, SubscriptionService $subscriptions, MidtransGateway $gateway): RedirectResponse
    {
        $isDemo = config('app.launch_mode') === 'demo';
        abort_unless($isDemo || config('services.midtrans.production') === false, 503, 'Production billing is disabled by launch policy.');
        abort_if(! $isDemo && ! config('features.payments_live'), 503, 'Payments are unavailable until live billing is enabled.');
        $data = $request->validate([
            'plan' => ['required', Rule::in(['plus', 'pro'])],
            'mode' => ['required', Rule::in(['auto', 'manual'])],
            'method' => ['required', 'string', 'max:30'],
            'consent' => ['required_if:mode,auto', 'accepted'],
            'upgrade_confirm' => ['sometimes', 'accepted'],
        ]);
        $plan = Plan::from($data['plan']);
        $mode = $data['mode'];
        $allowed = $settings->get("billing.methods.{$plan->value}.".($mode === 'auto' ? 'auto' : 'manual'), []);
        if ($plan === Plan::Plus) {
            $allowed = array_values(array_diff($allowed, ['bank_transfer']));
        }
        abort_unless(in_array($data['method'], $allowed, true), 422, 'Payment method is unavailable for this plan.');
        abort_if($mode === 'auto' && ! in_array($data['method'], ['credit_card', 'gopay'], true), 422);

        $current = Subscription::query()->where('user_id', $request->user()->id)->whereIn('status', ['active', 'past_due'])->latest()->first();
        if ($current && $current->getRawOriginal('plan') === Plan::Plus->value && $plan === Plan::Pro && ! ($data['upgrade_confirm'] ?? false)) {
            return back()->with('upgrade_warning', 'Upgrade starts immediately; remaining days are forfeited. Confirm this change before payment.')->withInput();
        }
        $amount = (int) $settings->get("plan.{$plan->value}.price_idr", config("plans.{$plan->value}.price_idr"));
        abort_if($amount < 1, 503, 'This plan is not available for checkout.');
        $subscription = $subscriptions->start($request->user(), $plan, $mode, $data['method']);
        if ($mode === 'auto') {
            DB::table('subscriptions')->where('id', $subscription->id)->update(['manual_reason' => json_encode(['consent' => $data['consent'], 'consented_at' => now()->toIso8601String(), 'consent_plan' => $plan->value])]);
        }
        $fee = $isDemo ? [] : $settings->get('billing.fee.'.$data['method'], config('billing.fee.'.$data['method'], []));
        $orderId = 'MF-'.$request->user()->id.'-'.now()->timestamp.'-'.Str::lower(Str::random(8));
        $payment = Payment::create(['user_id' => $request->user()->id, 'subscription_id' => $subscription->id, 'gateway_order_id' => $orderId, 'plan' => $plan, 'method' => $data['method'], 'amount' => $amount, 'est_fee_idr' => (int) round($amount * ($fee['rate'] ?? 0) + ($fee['fixed'] ?? 0))]);
        try {
            if ($isDemo) {
                return redirect()->route('billing.demo.checkout', $payment);
            }
            $snap = $gateway->snap($payment, $allowed, $mode === 'auto');
        } catch (\Throwable $exception) {
            $payment->delete();
            if ($subscription->getRawOriginal('status') === 'pending' && ! $subscription->payments()->where('status', 'paid')->exists()) {
                $subscription->delete();
            }
            throw $exception;
        }

        return redirect()->away($snap['redirect_url']);
    }

    public function webhook(Request $request, MidtransGateway $gateway): JsonResponse
    {
        $payload = $request->all();
        abort_unless($gateway->signatureValid($payload), 401);
        abort_unless(isset($payload['order_id'], $payload['status_code'], $payload['gross_amount']), 422);
        $isRecurring = ($payload['event_name'] ?? null) === 'subscription.charge';
        $orderId = $isRecurring
            ? (string) ($payload['subscription']['id'] ?? '')
            : (string) $payload['order_id'];
        abort_unless($orderId !== '', 422);
        $statusCode = (string) $payload['status_code'];
        $transactionId = (string) ($payload['transaction']['transaction_id'] ?? $payload['transaction_id'] ?? '');
        $eventKey = hash('sha256', implode('|', [$orderId, $payload['event_name'] ?? $payload['transaction_status'] ?? '', $transactionId, $statusCode]));
        $inserted = DB::table('webhook_events')->insertOrIgnore(['provider' => 'midtrans', 'event_key' => $eventKey, 'payload' => json_encode($payload), 'created_at' => now()]);
        if ($inserted) {
            ProcessMidtransWebhook::dispatch((int) DB::table('webhook_events')->where('event_key', $eventKey)->value('id'), $orderId);
        }

        return response()->json(['ok' => true]);
    }

    public function cancel(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        $subscription = Subscription::query()->where('user_id', $request->user()->id)->whereIn('status', ['active', 'past_due'])->latest()->firstOrFail();
        $subscriptions->cancel($subscription);

        return back();
    }

    public function resume(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        $subscription = Subscription::query()->where('user_id', $request->user()->id)->where('status', 'canceled')->where('current_period_end', '>', now())->latest()->firstOrFail();
        $subscriptions->resume($subscription);

        return back();
    }

    public function demoCheckout(Request $request, Payment $payment): View
    {
        abort_unless(config('app.launch_mode') === 'demo', 404);
        abort_unless($payment->user_id === $request->user()->id, 404);
        abort_unless($payment->getRawOriginal('status') === 'pending', 409);

        return view('billing.demo-checkout', ['payment' => $payment]);
    }

    public function demoComplete(Request $request, Payment $payment, SubscriptionService $subscriptions): RedirectResponse
    {
        abort_unless(config('app.launch_mode') === 'demo', 404);
        abort_unless($payment->user_id === $request->user()->id, 404);

        if ($payment->getRawOriginal('status') === 'pending') {
            $subscriptions->renew($payment->subscription, $payment);
        }

        return redirect()->route('account.show')->with('status', 'payment-simulated');
    }

    public function returned(): RedirectResponse
    {
        return redirect()->route('account.show')->with('status', 'payment-pending');
    }
}
