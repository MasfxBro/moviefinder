<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\User;
use App\Services\Ai\AiBudgetService;
use App\Services\Ai\AiCostCalculator;
use App\Services\Ai\FreePlaceService;
use App\Services\Ai\QuotaService;
use App\Services\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    if (DB::connection()->getDriverName() !== 'mysql') {
        $this->markTestSkipped('Quota behavior tests require MySQL.');
    }

    config([
        'app.business_timezone' => 'Asia/Jakarta',
        'ai.global_daily_cap' => 1000,
        'ai.free.pool_daily_limit' => 100,
        'ai.free.overbook_pct' => 100,
        'ai.free.waves' => [
            ['time' => '00:00', 'share' => 34],
            ['time' => '08:00', 'share' => 33],
            ['time' => '16:00', 'share' => 33],
        ],
        'ai.budget.smart_off_pct' => 60,
        'ai.budget.clip_search_off_pct' => 80,
        'ai.budget.pause_pct' => 100,
    ]);
});

it('preserves an existing Free place after the daily pool fills', function (): void {
    $now = CarbonImmutable::parse(now('Asia/Jakarta')->toDateString().' 08:00:00', 'Asia/Jakarta');
    $this->travelTo($now);
    $user = User::factory()->create();
    DB::table('ai_free_places')->insert(['used_on' => $now->toDateString(), 'user_id' => $user->id, 'taken_at' => $now]);
    DB::table('ai_free_pool')->insert(['used_on' => $now->toDateString(), 'taken' => 68]);

    expect(app(FreePlaceService::class)->take($user, $now))->toBeTrue();
});

it('opens waves at 08:00 and 16:00 WIB', function (): void {
    $places = app(FreePlaceService::class);

    expect($places->nextWave(CarbonImmutable::parse(now('Asia/Jakarta')->toDateString().' 07:59:59', 'Asia/Jakarta'))?->format('H:i'))->toBe('08:00')
        ->and($places->nextWave(CarbonImmutable::parse(now('Asia/Jakarta')->toDateString().' 08:00:00', 'Asia/Jakarta'))?->format('H:i'))->toBe('16:00');
});

it('charges clip search two daily quota units', function (): void {
    $now = CarbonImmutable::parse(now('Asia/Jakarta')->toDateString().' 12:00:00', 'Asia/Jakarta');
    $this->travelTo($now);
    $user = User::factory()->create();
    DB::table('ai_free_pool')->insert(['used_on' => $now->toDateString(), 'taken' => 0]);
    DB::table('ai_free_places')->insert(['used_on' => $now->toDateString(), 'user_id' => $user->id, 'taken_at' => $now]);

    app(QuotaService::class)->reserve($user, clipSearch: true);

    expect((int) DB::table('ai_usage')->where('user_id', $user->id)->value('count'))->toBe(2);
});

it('releases failed calls without logging cost', function (): void {
    $now = CarbonImmutable::parse(now('Asia/Jakarta')->toDateString().' 12:00:00', 'Asia/Jakarta');
    $this->travelTo($now);
    $user = User::factory()->create();
    DB::table('ai_free_pool')->insert(['used_on' => $now->toDateString(), 'taken' => 0]);
    DB::table('ai_free_places')->insert(['used_on' => $now->toDateString(), 'user_id' => $user->id, 'taken_at' => $now]);
    $quota = app(QuotaService::class);
    $reservation = $quota->reserve($user);
    $quota->release($user, $reservation);

    expect((int) DB::table('ai_usage')->where('user_id', $user->id)->value('count'))->toBe(0)
        ->and(DB::table('ai_usage_log')->where('user_id', $user->id)->count())->toBe(0);
});

it('starts a new daily quota after midnight WIB', function (): void {
    $this->travelTo(CarbonImmutable::parse(now('Asia/Jakarta')->toDateString().' 00:00:01', 'Asia/Jakarta'));
    $user = User::factory()->create();
    DB::table('ai_usage')->insert(['user_id' => $user->id, 'used_on' => '2026-11-05', 'count' => 5, 'smart_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('ai_free_pool')->insert(['used_on' => '2026-11-06', 'taken' => 0]);
    DB::table('ai_free_places')->insert(['used_on' => '2026-11-06', 'user_id' => $user->id, 'taken_at' => now()]);

    app(QuotaService::class)->reserve($user);

    expect((int) DB::table('ai_usage')->where('user_id', $user->id)->where('used_on', '2026-11-06')->value('count'))->toBe(1);
});

it('caps paid-period costs and clip allowance at their limits', function (): void {
    config(['plans.plus.budget_idr_period' => 100, 'plans.plus.ai_clip_searches_period' => 1]);
    $user = User::factory()->create();
    $start = CarbonImmutable::parse('2026-11-01', 'Asia/Jakarta');
    $end = CarbonImmutable::parse('2026-11-30', 'Asia/Jakarta');
    $budget = app(AiBudgetService::class);

    expect($budget->addCost($user, Plan::Plus, $start, $end, 60))->toBeTrue()
        ->and($budget->addCost($user, Plan::Plus, $start, $end, 20, clipSearch: true))->toBeTrue()
        ->and($budget->addCost($user, Plan::Plus, $start, $end, 21))->toBeFalse()
        ->and((int) DB::table('ai_budget')->where('user_id', $user->id)->value('spent_idr'))->toBe(80);
});

it('disables Smart, clip search, and AI at their exact budget thresholds', function (): void {
    config([
        'plans.pro.budget_idr_period' => 100,
        'plans.pro.ai_clip_searches_period' => 20,
    ]);
    $user = User::factory()->create();
    $start = CarbonImmutable::parse('2026-11-01', 'Asia/Jakarta');
    $end = CarbonImmutable::parse('2026-11-30', 'Asia/Jakarta');
    $budget = app(AiBudgetService::class);

    expect($budget->addCost($user, Plan::Pro, $start, $end, 60))->toBeTrue()
        ->and($budget->check($user, Plan::Pro, $start, $end)['smart'])->toBeFalse()
        ->and($budget->check($user, Plan::Pro, $start, $end)['clip_search'])->toBeTrue()
        ->and($budget->addCost($user, Plan::Pro, $start, $end, 20))->toBeTrue()
        ->and($budget->check($user, Plan::Pro, $start, $end)['clip_search'])->toBeFalse()
        ->and($budget->addCost($user, Plan::Pro, $start, $end, 20))->toBeTrue()
        ->and($budget->check($user, Plan::Pro, $start, $end)['allowed'])->toBeFalse();
});

it('changes estimated cost when the configured model price changes', function (): void {
    config(['ai.cost.standard_input' => 1000, 'ai.cost.standard_output' => 1000, 'ai.cost.safety_multiplier' => 1, 'ai.cost.grounded_search_fee_idr' => 5]);
    $settings = app(Settings::class);
    $calculator = app(AiCostCalculator::class);

    expect($calculator->calculate(1000, 1000, 'standard', true))->toBe(7);
    $settings->set('ai.cost.standard_input', 3000);
    expect($calculator->calculate(1000, 1000, 'standard', true))->toBe(9);
});
