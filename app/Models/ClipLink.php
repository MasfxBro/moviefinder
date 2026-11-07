<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClipLink extends Model
{
    protected $fillable = [
        'title_id', 'video_platform', 'video_id', 'url', 'channel_name', 'channel_id',
        'label', 'trust', 'source', 'scene_key', 'status', 'verified_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
