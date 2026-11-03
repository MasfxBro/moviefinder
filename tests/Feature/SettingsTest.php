<?php

declare(strict_types=1);

use App\Services\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('reads config default when no DB value', function (): void {
    $settings = new Settings;

    expect($settings->get('ai.cost.standard_input'))->toBe(4710);
    expect($settings->get('plan.free.daily_units'))->toBe(5);
    expect($settings->get('billing.grace_days'))->toBe(3);
});

test('reads DB value over config default', function (): void {
    DB::table('settings')->insert([
        'key' => 'plan.free.daily_units',
        'value' => json_encode(10),
        'updated_at' => now(),
    ]);

    $settings = new Settings;

    expect($settings->get('plan.free.daily_units'))->toBe(10);
});

test('caches DB reads for 60 seconds', function (): void {
    DB::table('settings')->insert([
        'key' => 'ai.cost.standard_input',
        'value' => json_encode(5000),
        'updated_at' => now(),
    ]);

    $settings = new Settings;

    // First call hits DB
    expect($settings->get('ai.cost.standard_input'))->toBe(5000);

    // Change DB value
    DB::table('settings')->where('key', 'ai.cost.standard_input')->update([
        'value' => json_encode(6000),
    ]);

    // Second call uses cache
    expect($settings->get('ai.cost.standard_input'))->toBe(5000);
});

test('invalidates cache on save', function (): void {
    $settings = new Settings;

    $settings->set('plan.plus.daily_units', 15);

    expect($settings->get('plan.plus.daily_units'))->toBe(15);

    // Change again
    $settings->set('plan.plus.daily_units', 20);

    expect($settings->get('plan.plus.daily_units'))->toBe(20);
});

test('set records updated_by', function (): void {
    $settings = new Settings;

    $settings->set('ai.cost.safety_multiplier', 1.2, 42);

    $row = DB::table('settings')->where('key', 'ai.cost.safety_multiplier')->first();

    expect($row)->not->toBeNull();
    expect($row->updated_by)->toBe(42);
    expect(json_decode($row->value))->toBe(1.2);
});
