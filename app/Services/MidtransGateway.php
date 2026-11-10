<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransGateway implements PaymentGateway
{
    public function snap(Payment $payment, array $methods, bool $autoRenew): array
    {
        $base = config('services.midtrans.production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->connectTimeout(3)->timeout(10)
            ->post($base.'/snap/v1/transactions', [
                'transaction_details' => ['order_id' => $payment->gateway_order_id, 'gross_amount' => $payment->amount],
                'customer_details' => ['email' => $payment->user->email, 'first_name' => $payment->user->name],
                'enabled_payments' => $methods,
                'credit_card' => ['secure' => true, 'save_card' => $autoRenew, 'installment' => ['terms' => ['bni' => [3], 'mandiri' => [3]]]],
                'gopay' => ['enable_callback' => true, 'callback_url' => route('account.show')],
                'recurring' => $autoRenew ? ['required' => true, 'start_time' => now()->addMinutes(10)->format('Y-m-d H:i:s O'), 'interval_unit' => 'month'] : null,
                'item_details' => [['id' => $payment->plan->value, 'price' => $payment->amount, 'quantity' => 1, 'name' => ucfirst($payment->plan->value).' monthly plan']],
                'callbacks' => ['finish' => route('account.show')],
                'custom_expiry' => ['order_time' => now()->format('Y-m-d H:i:s O'), 'expiry_duration' => 60, 'unit' => 'minute'],
            ])->throw()->json();

        return ['token' => $response['token'], 'redirect_url' => $response['redirect_url']];
    }

    public function gopayToken(string $accountId): array
    {
        $base = config('services.midtrans.production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';

        return Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->connectTimeout(3)->timeout(5)->get($base.'/v2/pay/account/'.$accountId)->throw()->json();
    }

    public function createSubscription(string $name, string $token, int $amount, string $method, ?string $accountId = null): array
    {
        $base = config('services.midtrans.production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';

        return Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->connectTimeout(3)->timeout(10)
            ->post($base.'/v1/subscriptions', [
                'name' => substr($name, 0, 40),
                'amount' => (string) $amount,
                'currency' => 'IDR',
                'payment_type' => $method === 'gopay' ? 'gopay' : 'credit_card',
                'token' => $token,
                'schedule' => ['interval' => 1, 'interval_unit' => 'month', 'max_interval' => 120, 'start_time' => now()->addMonth()->format('Y-m-d H:i:s O')],
                'retry_schedule' => ['interval' => 1, 'interval_unit' => 'day', 'max_interval' => 3],
                'gopay' => $method === 'gopay' ? ['account_id' => $accountId] : null,
            ])->throw()->json();
    }

    public function cancel(string $subscriptionId): void
    {
        $base = config('services.midtrans.production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->connectTimeout(3)->timeout(10)
            ->post($base.'/v1/subscriptions/'.$subscriptionId.'/disable')->throw();
    }

    public function resume(string $subscriptionId): void
    {
        $base = config('services.midtrans.production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->connectTimeout(3)->timeout(10)
            ->post($base.'/v1/subscriptions/'.$subscriptionId.'/enable')->throw();
    }

    public function updateSubscription(string $subscriptionId, int $amount, string $token, string $method, ?string $accountId = null): void
    {
        $base = config('services.midtrans.production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->connectTimeout(3)->timeout(10)
            ->patch($base.'/v1/subscriptions/'.$subscriptionId, [
                'name' => 'MovieFinder',
                'amount' => (string) $amount,
                'currency' => 'IDR',
                'token' => $token,
                'schedule' => ['interval' => 1],
                'gopay' => $method === 'gopay' ? ['account_id' => $accountId] : null,
            ])
            ->throw();
    }

    public function subscription(string $subscriptionId): array
    {
        $base = config('services.midtrans.production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';

        return Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->connectTimeout(3)->timeout(5)->get($base.'/v1/subscriptions/'.$subscriptionId)->throw()->json();
    }

    public function status(string $orderId): array
    {
        $base = config('services.midtrans.production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';

        return Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->connectTimeout(3)->timeout(5)->get($base.'/v2/'.$orderId.'/status')->throw()->json();
    }

    public function signatureValid(array $payload): bool
    {
        if (! isset($payload['order_id'], $payload['status_code'], $payload['gross_amount'], $payload['signature_key'])) {
            return false;
        }

        $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].(string) config('services.midtrans.server_key'));

        return hash_equals($expected, (string) $payload['signature_key']);
    }

    public function charge(Payment $payment): bool
    {
        throw new RuntimeException('Midtrans payments require a hosted Snap checkout.');
    }
}
