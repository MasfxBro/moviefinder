<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\PlanResolver;
use App\Enums\Plan;
use App\Models\User;
use App\Services\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuotaService
{
    public function __construct(private readonly PlanResolver $resolver, private readonly PlanCatalog $plans, private readonly FreePlaceService $places) {}

    public function reserve(User $user, bool $clipSearch = false, bool $smart = false): string
    {
        $plan = $this->resolver->resolve($user);
        $weight = $clipSearch ? 2 : 1;
        $token = bin2hex(random_bytes(16));
        $now = CarbonImmutable::now(config('app.business_timezone', 'Asia/Jakarta'));
        $day = $now->toDateString();

        if ($user->isAdmin()) {
            return $token;
        }
        if ($plan === Plan::Free && ! $this->places->take($user, $now)) {
            throw new RuntimeException('Free AI places are full.');
        }
        if ($smart && ($plan !== Plan::Pro || (int) $this->plans->get($plan)['smart_searches_daily'] < 1)) {
            throw new RuntimeException('Smart search is unavailable for this plan.');
        }

        return DB::transaction(function () use ($user, $plan, $weight, $smart, $day, $token): string {
            DB::table('ai_usage')->insertOrIgnore(['user_id' => $user->id, 'used_on' => $day, 'count' => 0, 'smart_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $limits = $this->plans->get($plan);
            $daily = $this->settingsValue('ai.global_daily_cap', PHP_INT_MAX);
            DB::table('ai_global_usage')->insertOrIgnore(['used_on' => $day, 'count' => 0]);
            DB::table('ai_global_usage')->where('used_on', $day)->lockForUpdate()->first();
            $globalUpdate = DB::table('ai_global_usage')->where('used_on', $day)->whereRaw('count + ? <= ?', [$weight, $daily])->update(['count' => DB::raw('count + '.$weight)]);
            if ($globalUpdate !== 1) {
                throw new RuntimeException('AI is at capacity right now.');
            }
            $usageUpdate = DB::table('ai_usage')->where('user_id', $user->id)->where('used_on', $day)->whereRaw('count + ? <= ?', [$weight, (int) $limits['daily_units']])->update(['count' => DB::raw('count + '.$weight)]);
            if ($usageUpdate !== 1) {
                DB::table('ai_global_usage')->where('used_on', $day)->decrement('count', $weight);
                throw new RuntimeException('Daily AI quota reached.');
            }
            if ($smart && DB::table('ai_usage')->where('user_id', $user->id)->where('used_on', $day)->whereRaw('smart_count + 1 <= ?', [(int) $limits['smart_searches_daily']])->update(['smart_count' => DB::raw('smart_count + 1')]) !== 1) {
                DB::table('ai_usage')->where('user_id', $user->id)->where('used_on', $day)->decrement('count', $weight);
                DB::table('ai_global_usage')->where('used_on', $day)->decrement('count', $weight);
                throw new RuntimeException('Daily Smart quota reached.');
            }

            return $token;
        }, attempts: 5);
    }

    public function release(User $user, string $reservation, bool $clipSearch = false, bool $smart = false): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $day = CarbonImmutable::now(config('app.business_timezone', 'Asia/Jakarta'))->toDateString();
        $weight = $clipSearch ? 2 : 1;
        DB::table('ai_usage')->where('user_id', $user->id)->where('used_on', $day)->where('count', '>=', $weight)->decrement('count', $weight);
        DB::table('ai_global_usage')->where('used_on', $day)->where('count', '>=', $weight)->decrement('count', $weight);
        if ($smart) {
            DB::table('ai_usage')->where('user_id', $user->id)->where('used_on', $day)->decrement('smart_count');
        }
    }

    public function commit(User $user, string $reservation, array $details): void
    {
        $cost = (int) ($details['cost_idr'] ?? 0);
        if ($user->isAdmin()) {
            return;
        }

        DB::table('ai_usage_log')->insert([
            'user_id' => $user->id,
            'mode' => $details['mode'] ?? 'global',
            'title_id' => $details['title_id'] ?? null,
            'model' => $details['model'] ?? null,
            'model_tier' => $details['model_tier'] ?? 'standard',
            'grounded' => (bool) ($details['grounded'] ?? false),
            'input_tokens' => $details['input_tokens'] ?? null,
            'output_tokens' => $details['output_tokens'] ?? null,
            'cost_idr' => $cost,
            'status' => $details['status'] ?? 'ok',
            'created_at' => now(),
        ]);
    }

    private function settingsValue(string $key, int $default): int
    {
        return (int) app(Settings::class)->get($key) ?: $default;
    }
}
