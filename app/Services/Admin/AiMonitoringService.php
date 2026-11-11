<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class AiMonitoringService
{
    public function usageReport(User $user): array
    {
        // Simplified: fetch from existing ai_usage tables (if they exist)
        $usage = DB::table('ai_usage')->where('user_id', $user->id)->first();

        return $usage ? (array) $usage : [];
    }

    /** @return array<string, array{revenue: int, fees: int, ai_cost: int, margin: int}> */
    public function profitByPlan(): array
    {
        $results = ['plus' => ['revenue' => 0, 'fees' => 0, 'ai_cost' => 0, 'margin' => 0], 'pro' => ['revenue' => 0, 'fees' => 0, 'ai_cost' => 0, 'margin' => 0]];
        foreach (DB::table('payments')->where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->groupBy('plan')->select('plan', DB::raw('sum(amount) as revenue'), DB::raw('sum(est_fee_idr) as fees'))->get() as $row) {
            $results[$row->plan]['revenue'] = (int) $row->revenue;
            $results[$row->plan]['fees'] = (int) $row->fees;
        }
        foreach (DB::table('ai_usage_log')->join('subscriptions', 'subscriptions.user_id', '=', 'ai_usage_log.user_id')->where('ai_usage_log.created_at', '>=', now()->startOfMonth())->whereIn('subscriptions.status', ['active', 'past_due', 'canceled'])->groupBy('subscriptions.plan')->select('subscriptions.plan', DB::raw('sum(ai_usage_log.cost_idr) as cost'))->get() as $row) {
            $results[$row->plan]['ai_cost'] = (int) $row->cost;
        }
        foreach ($results as &$plan) {
            $plan['margin'] = $plan['revenue'] - $plan['fees'] - $plan['ai_cost'];
        }
        unset($plan);

        return $results;
    }

    public function systemHealth(): array
    {
        return [
            'scheduler_heartbeat' => now()->toDateTimeString(),
            'ai_key_driver' => config('services.ai.driver', 'none'),
            'ai_keys_configured' => count(config('services.ai.keys', [])),
            'free_pool_used' => Cache::get('ai.free.pool_used', 0),
            'free_pool_limit' => config('ai.free.pool_daily_limit', 0),
            'queue_backlog' => Queue::size(),
            'queue_failed' => (int) DB::table('failed_jobs')->count(),
            'cache_hit_rate' => Cache::get('cache.hit_rate', null),
            'db_size_mb' => $this->dbSize(),
            'app_version' => app()->version(),
            'profit_by_plan' => $this->profitByPlan(),
        ];
    }

    private function dbSize(): float
    {
        $result = DB::selectOne('SELECT SUM(data_length+index_length) as size FROM information_schema.tables WHERE table_schema = DATABASE()');

        return $result->size / 1024 / 1024;
    }
}
