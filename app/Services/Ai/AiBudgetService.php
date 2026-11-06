<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\Plan;
use App\Models\User;
use App\Services\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AiBudgetService
{
    public function __construct(private readonly Settings $settings, private readonly PlanCatalog $plans) {}

    /** @return array{allowed: bool, smart: bool, clip_search: bool, spent_idr: int, clip_searches_used: int, renews_at: ?CarbonImmutable} */
    public function check(User $user, Plan $plan, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): array
    {
        if ($plan === Plan::Free || $user->isAdmin()) {
            return ['allowed' => true, 'smart' => false, 'clip_search' => false, 'spent_idr' => 0, 'clip_searches_used' => 0, 'renews_at' => null];
        }

        $row = $this->row($user, $windowStart, $windowEnd);
        $budget = max(1, (int) $this->plans->get($plan)['budget_idr_period']);
        $percent = $row->spent_idr / $budget * 100;
        $clipLimit = (int) $this->plans->get($plan)['ai_clip_searches_period'];

        return [
            'allowed' => $percent < (float) ($this->settings->get('ai.budget.pause_pct') ?? 100),
            'smart' => $plan === Plan::Pro && $percent < (float) ($this->settings->get('ai.budget.smart_off_pct') ?? 60),
            'clip_search' => $percent < (float) ($this->settings->get('ai.budget.clip_search_off_pct') ?? 80) && (int) $row->clip_searches < $clipLimit,
            'spent_idr' => (int) $row->spent_idr,
            'clip_searches_used' => (int) $row->clip_searches,
            'renews_at' => $windowEnd,
        ];
    }

    public function addCost(User $user, Plan $plan, CarbonImmutable $windowStart, CarbonImmutable $windowEnd, int $costIdr, bool $clipSearch = false): bool
    {
        if ($plan === Plan::Free || $user->isAdmin()) {
            return true;
        }

        return DB::transaction(function () use ($user, $windowStart, $windowEnd, $costIdr, $clipSearch, $plan): bool {
            DB::table('ai_budget')->insertOrIgnore([
                'user_id' => $user->id,
                'window_start' => $windowStart->toDateString(),
                'window_end' => $windowEnd->toDateString(),
                'spent_idr' => 0,
                'clip_searches' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $budget = max(0, (int) $this->plans->get($plan)['budget_idr_period']);
            $query = DB::table('ai_budget')->where('user_id', $user->id)->where('window_start', $windowStart->toDateString())->whereRaw('spent_idr + ? <= ?', [$costIdr, $budget]);
            if ($clipSearch) {
                $query->where('clip_searches', '<', (int) $this->plans->get($plan)['ai_clip_searches_period']);
            }
            $changes = ['spent_idr' => DB::raw('spent_idr + '.max(0, $costIdr)), 'updated_at' => now()];
            if ($clipSearch) {
                $changes['clip_searches'] = DB::raw('clip_searches + 1');
            }
            if ($query->update($changes) !== 1) {
                return false;
            }

            return true;
        }, attempts: 5);
    }

    private function row(User $user, CarbonImmutable $start, CarbonImmutable $end): object
    {
        DB::table('ai_budget')->insertOrIgnore([
            'user_id' => $user->id,
            'window_start' => $start->toDateString(),
            'window_end' => $end->toDateString(),
            'spent_idr' => 0,
            'clip_searches' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('ai_budget')->where('user_id', $user->id)->where('window_start', $start->toDateString())->first();
    }
}
