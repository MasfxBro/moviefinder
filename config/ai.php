<?php

declare(strict_types=1);

return [
    'cost' => [
        'standard_input' => 4710,
        'standard_output' => 39250,
        'smart_input' => 11775,
        'smart_output' => 58875,
        'grounded_search_fee' => 220,
        'safety_multiplier' => 1.15,
    ],

    'budget' => [
        'smart_off_pct' => 60,
        'grounded_off_pct' => 80,
    ],

    'free' => [
        'pool_daily_limit' => 1500,
        'overbook_pct' => 100,
        'waves' => [
            ['time' => '00:00', 'share' => 34],
            ['time' => '08:00', 'share' => 33],
            ['time' => '16:00', 'share' => 33],
        ],
    ],

    'global_daily_cap' => env('AI_GLOBAL_DAILY_CAP', 10000),
    'title_reply_max' => 600,
];
