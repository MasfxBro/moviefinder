<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;

use function Tests\Support\addBudgetCostTask;
use function Tests\Support\reserveTask;
use function Tests\Support\takePlaceTask;

require_once __DIR__.'/../Support/QuotaConcurrencyTasks.php';

beforeEach(function (): void {
    if (DB::connection()->getDriverName() !== 'mysql') {
        $this->markTestSkipped('Quota concurrency tests require MySQL.');
    }

    config([
        'plans.plus.budget_idr_period' => 100,
        'plans.plus.ai_clip_searches_period' => 100,
        'app.business_timezone' => 'Asia/Jakarta',
        'ai.global_daily_cap' => 1000,
        'ai.free.pool_daily_limit' => 100,
        'ai.free.overbook_pct' => 100,
        'ai.free.waves' => [
            ['time' => '00:00', 'share' => 34],
            ['time' => '08:00', 'share' => 33],
            ['time' => '16:00', 'share' => 33],
        ],
    ]);
});

it('admits exactly five of twenty concurrent reservations with five units left', function (): void {
    $user = User::factory()->create();
    $day = now('Asia/Jakarta')->toDateString();
    DB::table('ai_usage')->insert(['user_id' => $user->id, 'used_on' => $day, 'count' => 0, 'smart_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('ai_free_pool')->insert(['used_on' => $day, 'taken' => 1]);
    DB::table('ai_free_places')->insert(['used_on' => $day, 'user_id' => $user->id, 'taken_at' => now()]);

    $results = Concurrency::driver('process')->run(array_map(static fn () => reserveTask((int) $user->id, $day), range(1, 20)));

    expect(count(array_filter($results)))->toBe(5);
});

it('never spends beyond a paid budget under concurrent calls', function (): void {
    $user = User::factory()->create();
    $start = CarbonImmutable::parse('2026-11-01', 'Asia/Jakarta');
    $end = CarbonImmutable::parse('2026-11-30', 'Asia/Jakarta');
    $tasks = array_map(static fn () => addBudgetCostTask((int) $user->id, $start, $end), range(1, 20));

    $results = Concurrency::driver('process')->run($tasks);

    expect(count(array_filter($results)))->toBeLessThanOrEqual(10)
        ->and((int) DB::table('ai_budget')->where('user_id', $user->id)->value('spent_idr'))->toBeLessThanOrEqual(100);
});

it('enforces the global daily reservation cap across users', function (): void {
    $day = now('Asia/Jakarta')->toDateString();
    $users = User::factory()->count(20)->create();
    DB::table('ai_free_pool')->insert(['used_on' => $day, 'taken' => 0]);
    DB::table('ai_free_places')->insert($users->map(static fn (User $user): array => ['used_on' => $day, 'user_id' => $user->id, 'taken_at' => now()])->all());

    $results = Concurrency::driver('process')->run(array_map(static fn (int $id) => reserveTask($id, $day, 5), $users->modelKeys()));

    expect(count(array_filter($results)))->toBe(5)
        ->and((int) DB::table('ai_global_usage')->where('used_on', $day)->value('count'))->toBe(5);
});

it('never distributes more concurrent Free places than the released wave', function (): void {
    $now = CarbonImmutable::parse(now('Asia/Jakarta')->toDateString().' 08:00:00', 'Asia/Jakarta');
    $this->travelTo($now);
    $users = User::factory()->count(20)->create();

    $results = Concurrency::driver('process')->run(array_map(static fn (int $id) => takePlaceTask($id, $now), $users->modelKeys()));

    expect((int) DB::table('ai_free_pool')->where('used_on', $now->toDateString())->value('taken'))->toBeLessThanOrEqual(68)
        ->and((int) DB::table('ai_free_places')->where('used_on', $now->toDateString())->count())->toBeLessThanOrEqual(68);
});
