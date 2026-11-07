<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\PlanResolver;
use App\Enums\Plan;
use App\Models\User;

class ModelRouter
{
    public function __construct(private readonly PlanResolver $plans) {}

    /** @return array{model: string, tier: string, plan: string, key: string} */
    public function route(User $user, bool $smartAvailable): array
    {
        $plan = $this->plans->resolve($user);
        $smart = $plan === Plan::Pro && $smartAvailable;

        return [
            'model' => (string) config($smart ? 'ai.models.smart' : 'ai.models.standard', ''),
            'tier' => $smart ? 'smart' : 'standard',
            'plan' => $plan->value,
            'key' => $plan === Plan::Free ? (string) config('services.gemini.free_key', '') : (string) config('services.gemini.paid_key', ''),
        ];
    }
}
