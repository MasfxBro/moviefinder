<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Plan;
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
                    $query->whereIn('status', ['active', 'past_due', 'canceled'])
                        ->where(function ($query) use ($now): void {
                            $query->where('current_period_end', '>', $now)->orWhere('grace_ends_at', '>', $now);
                        });
                })->orWhere(function ($query) use ($now): void {
                    $query->where('source', 'manual')->where('status', 'active')->where('current_period_end', '>', $now);
                });
            })
            ->pluck('plan')->map(fn ($plan): Plan => $plan instanceof Plan ? $plan : Plan::from($plan));

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
