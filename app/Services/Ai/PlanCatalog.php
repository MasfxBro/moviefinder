<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\Plan;
use App\Services\Settings;

class PlanCatalog
{
    public function __construct(private readonly Settings $settings) {}

    /** @return array<string, mixed> */
    public function get(Plan $plan): array
    {
        $defaults = config('plans.'.$plan->value, []);
        $limits = [];

        foreach ($defaults as $key => $default) {
            $limits[$key] = $this->settings->get('plan.'.$plan->value.'.'.$key) ?? $default;
        }

        return $limits;
    }
}
