<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\PlanResolver;
use App\Enums\Plan;
use App\Models\User;

class FreePlanResolver implements PlanResolver
{
    public function resolve(User $user): Plan
    {
        return Plan::Free;
    }
}
