<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Plan;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'plan', 'billing_mode', 'payment_method', 'pending_plan', 'status', 'source', 'gateway_subscription_id', 'current_period_start', 'current_period_end', 'cancel_at_period_end', 'canceled_at', 'grace_ends_at', 'manual_reason', 'granted_by'])]
class Subscription extends Model
{
    protected function casts(): array
    {
        return ['plan' => Plan::class, 'pending_plan' => Plan::class, 'status' => SubscriptionStatus::class, 'current_period_start' => 'immutable_datetime', 'current_period_end' => 'immutable_datetime', 'canceled_at' => 'immutable_datetime', 'grace_ends_at' => 'immutable_datetime', 'cancel_at_period_end' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
