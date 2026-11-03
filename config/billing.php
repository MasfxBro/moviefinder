<?php

declare(strict_types=1);

return [
    'grace_days' => 3,

    'methods' => [
        'plus' => [
            'auto' => ['credit_card', 'gopay'],
            'manual' => ['qris', 'gopay', 'shopeepay', 'credit_card'],
        ],
        'pro' => [
            'auto' => ['credit_card', 'gopay'],
            'manual' => ['qris', 'gopay', 'shopeepay', 'credit_card', 'bank_transfer'],
        ],
    ],

    'fee' => [
        'credit_card' => ['rate' => 0.029, 'fixed' => 2000],
        'qris' => ['rate' => 0.007, 'fixed' => 0],
        'gopay' => ['rate' => 0.02, 'fixed' => 0],
        'shopeepay' => ['rate' => 0.02, 'fixed' => 0],
        'bank_transfer' => ['rate' => 0, 'fixed' => 4000],
    ],
];
