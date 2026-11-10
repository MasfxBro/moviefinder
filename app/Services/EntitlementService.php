<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Ai\PlanCatalog;
use Illuminate\Support\Carbon;

class EntitlementService
{
    public function plan(User $user): Plan
    {
        $now = now();
        $plans = $user->subscriptions()
            ->where(function ($query) use ($now): void {
                $query->where(function ($query) use ($now): void {
                    $query->where(function ($active) use ($now): void {
                        $active->whereIn('status', ['active', 'canceled'])->where('current_period_end', '>', $now);
                    })->orWhere(function ($grace) use ($now): void {
                        $grace->where('status', 'past_due')->where('grace_ends_at', '>', $now);
                    });
                })->orWhere(function ($query) use ($now): void {
                    $query->where('source', 'manual')->where('status', 'active')->where('current_period_end', '>', $now);
                });
            })
            ->get(['plan', 'billing_mode', 'status', 'current_period_end', 'grace_ends_at', 'source'])
            ->map(fn (Subscription $subscription): Plan => $subscription->plan);

        return $plans->contains(Plan::Pro) ? Plan::Pro : ($plans->contains(Plan::Plus) ? Plan::Plus : Plan::Free);
    }

    /** @return array<string, mixed> */
    public function limits(User $user, PlanCatalog $catalog): array
    {
        return $catalog->get($this->plan($user));
    }

    public function grant(User $user, Plan $plan, string $reason, Carbon $expiresAt, ?User $grantedBy = null): void
    {
        $user->subscriptions()->create([
            'plan' => $plan,
            'billing_mode' => 'manual',
            'status' => 'active',
            'source' => 'manual',
            'manual_reason' => $reason,
            'granted_by' => $grantedBy?->id,
            'current_period_start' => now(),
            'current_period_end' => $expiresAt,
        ]);
    }
}
