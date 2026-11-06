<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\Plan;
use App\Models\User;
use App\Services\Ai\AiBudgetService;
use App\Services\Ai\FreePlaceService;
use App\Services\Ai\QuotaService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Throwable;

function reserveTask(int $userId, string $day, int $globalCap = 1000): Closure
{
    return static function () use ($userId, $day, $globalCap): bool {
        Config::set([
            'app.business_timezone' => 'Asia/Jakarta',
            'ai.global_daily_cap' => $globalCap,
            'ai.free.pool_daily_limit' => 100,
            'ai.free.overbook_pct' => 100,
            'ai.free.waves' => [['time' => '00:00', 'share' => 34], ['time' => '08:00', 'share' => 33], ['time' => '16:00', 'share' => 33]],
            'plans.free.daily_units' => 5,
        ]);
        Carbon::setTestNow(CarbonImmutable::parse($day.' 12:00:00', 'Asia/Jakarta'));

        try {
            app(QuotaService::class)->reserve(User::findOrFail($userId));

            return true;
        } catch (RuntimeException) {
            return false;
        }
    };
}

function takePlaceTask(int $userId, CarbonImmutable $now): Closure
{
    return static function () use ($userId, $now): bool {
        Config::set([
            'app.business_timezone' => 'Asia/Jakarta',
            'ai.free.pool_daily_limit' => 100,
            'ai.free.overbook_pct' => 100,
            'ai.free.waves' => [['time' => '00:00', 'share' => 34], ['time' => '08:00', 'share' => 33], ['time' => '16:00', 'share' => 33]],
            'plans.free.daily_units' => 5,
        ]);
        Carbon::setTestNow($now);

        try {
            return app(FreePlaceService::class)->take(User::findOrFail($userId), $now);
        } catch (Throwable) {
            return false;
        }
    };
}

function addBudgetCostTask(int $userId, CarbonImmutable $start, CarbonImmutable $end): Closure
{
    return static function () use ($userId, $start, $end): bool {
        Config::set(['plans.plus.budget_idr_period' => 100, 'plans.plus.ai_clip_searches_period' => 100]);

        try {
            return app(AiBudgetService::class)->addCost(User::findOrFail($userId), Plan::Plus, $start, $end, 10);
        } catch (Throwable) {
            return false;
        }
    };
}
