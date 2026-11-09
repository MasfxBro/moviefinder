<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Payment;

class FakePaymentGateway implements PaymentGateway
{
    public function charge(Payment $payment): bool
    {
        return true;
    }

    public function cancel(string $subscriptionId): void {}
}
