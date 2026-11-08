<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchMiss extends Model
{
    protected $table = 'search_misses';

    protected $fillable = [
        'query_norm',
        'hits',
        'last_seen_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hits' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }
}
