<?php

declare(strict_types=1);

return [
    'ai_enabled' => env('FEATURE_AI_ENABLED', true),
    'ai_clip_search' => env('FEATURE_AI_CLIP_SEARCH', true),
    'payments_live' => env('FEATURE_PAYMENTS_LIVE', false),
    'registration_open' => env('FEATURE_REGISTRATION_OPEN', true),
    'requests_open' => env('FEATURE_REQUESTS_OPEN', true),
    'feedback_open' => env('FEATURE_FEEDBACK_OPEN', true),
    'clip_driver_youtube_api' => env('FEATURE_CLIP_DRIVER_YOUTUBE_API', false),
];
