<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Plan;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EntitlementService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminBillingController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = Subscription::query()->with('user')->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('plan'), fn ($query, $plan) => $query->where('plan', $plan))
            ->latest()->paginate(25)->withQueryString();
        $payments = Payment::query()->with('user')->when($request->query('payment_status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate(25, ['*'], 'payments_page')->withQueryString();

        return view('admin.billing.index', compact('subscriptions', 'payments'));
    }

    public function show(Subscription $subscription): View
    {
        return view('admin.billing.show', ['subscription' => $subscription->load(['user', 'payments' => fn ($query) => $query->latest()])]);
    }

    public function cancel(Request $request, Subscription $subscription, SubscriptionService $service, AuditLogger $audit): RedirectResponse
    {
        $service->cancel($subscription);
        if ($subscription->billing_mode === 'manual') {
            $subscription->update(['status' => SubscriptionStatus::Expired, 'current_period_end' => now()]);
        }
        $audit->log($request, 'billing.subscription.cancel', $subscription);

        return back()->with('success', 'Subscription canceled.');
    }

    public function grant(Request $request, EntitlementService $entitlements, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id'], 'plan' => ['required', 'in:plus,pro'], 'reason' => ['required', 'string', 'max:255'], 'expires_at' => ['required', 'date', 'after:now']]);
        $user = User::findOrFail($data['user_id']);
        $entitlements->grant($user, Plan::from($data['plan']), $data['reason'], now()->parse($data['expires_at']), $request->user('admin'));
        $audit->log($request, 'billing.subscription.grant', $user, $data);

        return back()->with('success', 'Manual grant created.');
    }

    public function export(Request $request): StreamedResponse
    {
        $payments = Payment::query()->with('user')->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('plan'), fn ($query, $plan) => $query->where('plan', $plan))->latest();

        return response()->streamDownload(function () use ($payments): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['order_id', 'email', 'plan', 'method', 'amount_idr', 'estimated_fee_idr', 'status', 'paid_at']);
            $payments->chunk(500, function ($items) use ($stream): void {
                foreach ($items as $payment) {
                    fputcsv($stream, [$payment->gateway_order_id, $payment->user?->email, $payment->plan->value, $payment->method, $payment->amount, $payment->est_fee_idr, $payment->status->value, $payment->paid_at?->toDateTimeString()]);
                }
            });
            fclose($stream);
        }, 'billing-payments.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
