<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\Plan;
use App\Models\User;
use App\Services\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class FreePlaceService
{
    public function __construct(private readonly Settings $settings, private readonly PlanCatalog $plans) {}

    public function take(User $user, CarbonImmutable $now): bool
    {
        $day = $now->setTimezone(config('app.business_timezone', 'Asia/Jakarta'))->toDateString();

        return DB::transaction(function () use ($user, $now, $day): bool {
            DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            if (DB::table('ai_free_places')->where('used_on', $day)->where('user_id', $user->id)->exists()) {
                return true;
            }

            DB::table('ai_free_pool')->insertOrIgnore(['used_on' => $day, 'taken' => 0]);
            DB::table('ai_free_pool')->where('used_on', $day)->lockForUpdate()->first();
            $limit = (int) $this->settings->get('ai.free.pool_daily_limit');
            $dailyUnits = max(1, (int) ($this->plans->get(Plan::Free)['daily_units'] ?? 5));
            $places = (int) floor($limit / $dailyUnits * ((float) $this->settings->get('ai.free.overbook_pct') / 100));
            $released = (int) floor($places * $this->releasedShare($now));

            if (DB::table('ai_free_pool')->where('used_on', $day)->where('taken', '<', $released)->update(['taken' => DB::raw('taken + 1')]) !== 1) {
                return false;
            }

            DB::table('ai_free_places')->insert(['used_on' => $day, 'user_id' => $user->id, 'taken_at' => $now]);

            return true;
        }, attempts: 5);
    }

    public function holds(User $user, CarbonImmutable $now): bool
    {
        return DB::table('ai_free_places')->where('used_on', $now->setTimezone(config('app.business_timezone', 'Asia/Jakarta'))->toDateString())->where('user_id', $user->id)->exists();
    }

    public function isFull(CarbonImmutable $now): bool
    {
        $day = $now->setTimezone(config('app.business_timezone', 'Asia/Jakarta'))->toDateString();
        $pool = DB::table('ai_free_pool')->where('used_on', $day)->first();
        $limit = (int) $this->settings->get('ai.free.pool_daily_limit');
        $dailyUnits = max(1, (int) ($this->plans->get(Plan::Free)['daily_units'] ?? 5));
        $places = (int) floor($limit / $dailyUnits * ((float) $this->settings->get('ai.free.overbook_pct') / 100));

        return (int) ($pool->taken ?? 0) >= (int) floor($places * $this->releasedShare($now));
    }

    public function nextWave(CarbonImmutable $now): ?CarbonImmutable
    {
        $timezone = config('app.business_timezone', 'Asia/Jakarta');
        $local = $now->setTimezone($timezone);

        foreach ($this->waves() as $wave) {
            $at = $local->startOfDay()->setTimeFromTimeString($wave['time']);
            if ($at->greaterThan($local)) {
                return $at;
            }
        }

        return $local->addDay()->startOfDay();
    }

    private function releasedShare(CarbonImmutable $now): float
    {
        $local = $now->setTimezone(config('app.business_timezone', 'Asia/Jakarta'));
        $share = 0;

        foreach ($this->waves() as $wave) {
            if ($local->format('H:i:s') >= $wave['time'].':00') {
                $share += (int) $wave['share'];
            }
        }

        return min(1, $share / 100);
    }

    /** @return array<int, array{time: string, share: int}> */
    private function waves(): array
    {
        return $this->settings->get('ai.free.waves') ?? [
            ['time' => '00:00', 'share' => 34],
            ['time' => '08:00', 'share' => 33],
            ['time' => '16:00', 'share' => 33],
        ];
    }
}
