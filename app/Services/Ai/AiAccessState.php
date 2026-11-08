<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\PlanResolver;
use App\Enums\Plan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AiAccessState
{
    public function __construct(private readonly PlanResolver $resolver, private readonly PlanCatalog $plans, private readonly FreePlaceService $places, private readonly AiBudgetService $budget) {}

    /** @return array<string, mixed> */
    public function for(User $user, ?CarbonImmutable $periodStart = null, ?CarbonImmutable $periodEnd = null): array
    {
        $timezone = config('app.business_timezone', 'Asia/Jakarta');
        $now = CarbonImmutable::now($timezone);
        $plan = $this->resolver->resolve($user);
        $day = $now->toDateString();
        $usage = DB::table('ai_usage')->where('user_id', $user->id)->where('used_on', $day)->first();
        $limits = $this->plans->get($plan);
        $count = (int) ($usage->count ?? 0);
        $smartCount = (int) ($usage->smart_count ?? 0);
        $placeHeld = $plan === Plan::Free && $this->places->holds($user, $now);
        $budgetState = $this->budget->check($user, $plan, $periodStart ?? $now->startOfMonth(), $periodEnd ?? $now->endOfMonth());

        return [
            'plan' => $plan->value,
            'units_left' => max(0, (int) $limits['daily_units'] - $count),
            'units_limit' => (int) $limits['daily_units'],
            'smart_left' => max(0, (int) $limits['smart_searches_daily'] - $smartCount),
            'smart_limit' => (int) $limits['smart_searches_daily'],
            'pro_limit' => (int) ($this->plans->get(Plan::Pro)['daily_units']),
            'place_held' => $placeHeld,
            'places_full' => $plan === Plan::Free && ! $placeHeld && $this->places->isFull($now),
            'next_wave_at' => $this->places->nextWave($now)?->toIso8601String(),
            'daily_limit_used' => $count >= (int) $limits['daily_units'],
            'daily_reset_at' => $now->addDay()->startOfDay()->toIso8601String(),
            'allowance_used' => (int) ($budgetState['clip_searches_used']),
            'allowance_limit' => (int) $limits['ai_clip_searches_period'],
            'renews_at' => $budgetState['renews_at']?->toIso8601String(),
            'budget' => $budgetState,
        ];
    }
}
