<?php

declare(strict_types=1);

return [
    'free' => [
        'price_idr' => 0,
        'daily_units' => 5,
        'smart_searches_daily' => 0,
        'ai_clip_searches_period' => 0,
        'budget_idr_period' => 0,
        'title_cards' => 3,
        'clip_candidates' => 0,
        'clips_shown' => 2,
        'conversation_turns' => 8,
        'why_matches_style' => 'short',
        'critic_enabled' => false,
        'watchlist_max' => 100,
        'requests_daily' => 2,
        'ai_rate_limit_per_minute' => 6,
    ],

    'plus' => [
        'price_idr' => 15000,
        'daily_units' => 10,
        'smart_searches_daily' => 0,
        'ai_clip_searches_period' => 10,
        'budget_idr_period' => 6000,
        'title_cards' => 4,
        'clip_candidates' => 5,
        'clips_shown' => 4,
        'conversation_turns' => 12,
        'why_matches_style' => 'medium',
        'critic_enabled' => false,
        'watchlist_max' => 300,
        'requests_daily' => 5,
        'ai_rate_limit_per_minute' => 8,
    ],

    'pro' => [
        'price_idr' => 28000,
        'daily_units' => 12,
        'smart_searches_daily' => 4,
        'ai_clip_searches_period' => 20,
        'budget_idr_period' => 11300,
        'title_cards' => 5,
        'clip_candidates' => 8,
        'clips_shown' => 6,
        'conversation_turns' => 16,
        'why_matches_style' => 'detailed',
        'critic_enabled' => true,
        'watchlist_max' => 500,
        'requests_daily' => 10,
        'ai_rate_limit_per_minute' => 12,
    ],
];
