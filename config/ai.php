<?php

return [
    'driver' => env('AI_DRIVER', 'fake'),
    'cost' => [
        'standard_input' => 4710,
        'standard_output' => 18800,
        'smart_input' => 4710,
        'smart_output' => 18800,
        'safety_multiplier' => 1.5,
        'grounded_search_fee_idr' => 0,
    ],
    'budget' => [
        'smart_off_pct' => 60,
        'clip_search_off_pct' => 80,
        'pause_pct' => 100,
    ],
    'free' => [
        'pool_daily_limit' => 0,
        'overbook_pct' => 100,
        'waves' => [
            ['time' => '00:00', 'share' => 34],
            ['time' => '08:00', 'share' => 33],
            ['time' => '16:00', 'share' => 33],
        ],
    ],
    'global_daily_cap' => 0,
    'models' => [
        'standard' => env('AI_MODEL_STANDARD', ''),
        'smart' => env('AI_MODEL_SMART', ''),
    ],
    'title_reply_max' => 600,
    'reply_max' => 300,
    'candidate_max' => 5,
    'free_candidate_max' => 3,
    'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
];
