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

    public function systemHealth(): array
    {
        return [
            'scheduler_heartbeat' => now()->toDateTimeString(),
            'ai_key_driver' => config('services.ai.driver', 'none'),
            'ai_keys_configured' => count(config('services.ai.keys', [])),
            'free_pool_used' => Cache::get('ai.free.pool_used', 0),
            'free_pool_limit' => config('ai.free.pool_daily_limit', 0),
            'queue_backlog' => Queue::size(),
            'queue_failed' => Queue::failedJobs()->count(),
            'cache_hit_rate' => Cache::get('cache.hit_rate', null),
            'db_size_mb' => $this->dbSize(),
            'app_version' => app()->version(),
        ];
    }

    private function dbSize(): float
    {
        $result = DB::selectOne('SELECT SUM(data_length+index_length) as size FROM information_schema.tables WHERE table_schema = DATABASE()');

        return $result->size / 1024 / 1024;
    }
}
