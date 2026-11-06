<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Settings;

class AiCostCalculator
{
    public function __construct(private readonly Settings $settings) {}

    public function calculate(int $inputTokens, int $outputTokens, string $modelTier, bool $grounded = false): int
    {
        $prefix = 'ai.cost.'.($modelTier === 'smart' ? 'smart' : 'standard');
        $input = (float) $this->settings->get($prefix.'_input');
        $output = (float) $this->settings->get($prefix.'_output');
        $multiplier = (float) ($this->settings->get('ai.cost.safety_multiplier') ?? 1);
        $groundedFee = $grounded ? (float) ($this->settings->get('ai.cost.grounded_search_fee_idr') ?? 0) : 0;

        return (int) ceil((($inputTokens * $input + $outputTokens * $output) / 1_000_000 * $multiplier) + $groundedFee);
    }
}
