<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\Plan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $gateway_order_id
 * @property Plan $plan
 * @property int $id
 * @property PaymentStatus $status
 */
#[Fillable(['user_id', 'subscription_id', 'gateway_order_id', 'plan', 'method', 'amount', 'est_fee_idr', 'currency', 'status', 'paid_at', 'failure_reason'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return ['plan' => Plan::class, 'status' => PaymentStatus::class, 'paid_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
