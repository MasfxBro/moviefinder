<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Plan;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Plan $plan
 * @property Plan|null $pending_plan
 * @property SubscriptionStatus $status
 * @property int $id
 * @property string|null $gateway_subscription_id
 * @property string $source
 * @property string|null $gateway_account_id
 * @property int $id
 * @property string|null $gateway_token
 * @property string $billing_mode
 * @property string|null $payment_method
 * @property Carbon|null $current_period_end
 * @property Plan $plan
 * @property SubscriptionStatus $status
 * @property Carbon|null $grace_ends_at
 * @property bool $cancel_at_period_end
 */
#[Fillable(['user_id', 'plan', 'billing_mode', 'payment_method', 'pending_plan', 'status', 'source', 'gateway_subscription_id', 'gateway_token', 'gateway_account_id', 'current_period_start', 'current_period_end', 'cancel_at_period_end', 'canceled_at', 'grace_ends_at', 'manual_reason', 'granted_by'])]
class Subscription extends Model
{
    protected function casts(): array
    {
        return ['plan' => Plan::class, 'pending_plan' => Plan::class, 'status' => SubscriptionStatus::class, 'current_period_start' => 'immutable_datetime', 'current_period_end' => 'immutable_datetime', 'canceled_at' => 'immutable_datetime', 'grace_ends_at' => 'immutable_datetime', 'gateway_token' => 'encrypted', 'cancel_at_period_end' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
