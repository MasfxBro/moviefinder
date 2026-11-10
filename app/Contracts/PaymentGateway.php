<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Payment;

interface PaymentGateway
{
    public function charge(Payment $payment): bool;

    public function cancel(string $subscriptionId): void;

    public function updateSubscription(string $subscriptionId, int $amount, string $token, string $method, ?string $accountId = null): void;

    public function resume(string $subscriptionId): void;
}
