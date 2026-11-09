<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PlanResolver;
use App\Enums\Plan;
use App\Models\User;

class BillingPlanResolver implements PlanResolver
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function resolve(User $user): Plan
    {
        return $this->entitlements->plan($user);
    }
}
