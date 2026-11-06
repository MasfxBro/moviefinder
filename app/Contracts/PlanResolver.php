<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\Plan;
use App\Models\User;

interface PlanResolver
{
    public function resolve(User $user): Plan;
}
