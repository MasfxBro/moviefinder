<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Settings
{
    private const CACHE_TTL = 60;

    private const CACHE_KEY_PREFIX = 'v1:settings:';

    /**
     * Get a setting value by key.
     * Reads from DB (cached 60 seconds), falls back to config default.
     *
     * @param  string  $key  Dot-notation key (e.g. 'ai.cost.standard_input')
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default): mixed {
            $row = DB::table('settings')->where('key', $key)->first();

            if ($row !== null) {
                return json_decode($row->value, true);
            }

            return $this->getConfigDefault($key) ?? $default;
        });
    }

    /**
     * Set a setting value and invalidate cache.
     *
     * @param  int|null  $updatedBy  User ID
     */
    public function set(string $key, mixed $value, ?int $updatedBy = null): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => json_encode($value),
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ]
        );

        $this->invalidate($key);
    }

    /**
     * Invalidate cache for a key.
     */
    public function invalidate(string $key): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX.$key);
    }

    /**
     * Get the config default for a key.
     * Maps dot notation to nested config arrays.
     */
    private function getConfigDefault(string $key): mixed
    {
        // Map settings keys to config locations
        $parts = explode('.', $key);

        // plan.free.daily_units -> config('plans.free.daily_units')
        if ($parts[0] === 'plan' && isset($parts[1])) {
            return config('plans.'.$parts[1].'.'.implode('.', array_slice($parts, 2)));
        }

        // ai.cost.standard_input -> config('ai.cost.standard_input')
        // ai.budget.smart_off_pct -> config('ai.budget.smart_off_pct')
        // ai.free.pool_daily_limit -> config('ai.free.pool_daily_limit')
        if ($parts[0] === 'ai') {
            return config('ai.'.implode('.', array_slice($parts, 1)));
        }

        // billing.grace_days -> config('billing.grace_days')
        // billing.methods.plus.auto -> config('billing.methods.plus.auto')
        // billing.fee.credit_card.rate -> config('billing.fee.credit_card.rate')
        if ($parts[0] === 'billing') {
            return config('billing.'.implode('.', array_slice($parts, 1)));
        }

        // clips.grounded_daily_budget -> config('clips.grounded_daily_budget')
        if ($parts[0] === 'clips') {
            return config('clips.'.implode('.', array_slice($parts, 1)));
        }

        // auth.unverified_ttl_days -> config('auth.password_timeout') doesn't exist, use a default
        if ($key === 'auth.unverified_ttl_days') {
            return 14;
        }

        // onboarding.enabled -> default true
        if ($key === 'onboarding.enabled') {
            return true;
        }

        // features.* -> config('features.*')
        if ($parts[0] === 'features') {
            return config('features.'.implode('.', array_slice($parts, 1)));
        }

        // site.* -> no config default, return null
        if ($parts[0] === 'site') {
            return null;
        }

        return null;
    }
}
