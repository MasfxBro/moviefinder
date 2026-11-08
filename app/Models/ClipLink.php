<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property bool $is_embeddable
 * @property string $source_label
 */
class ClipLink extends Model
{
    use HasFactory;

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

    public function reports(): HasMany
    {
        return $this->hasMany(LinkReport::class);
    }

    public function isEmbeddable(): bool
    {
        return $this->status === 'active'
            && $this->video_platform === 'youtube'
            && filled($this->video_id)
            && in_array($this->trust, ['official', 'trusted'], true);
    }

    public function getIsEmbeddableAttribute(): bool
    {
        return $this->isEmbeddable();
    }

    public function sourceLabel(): string
    {
        if (! in_array($this->video_platform, ['youtube', 'vimeo', 'dailymotion', 'tiktok'], true)) {
            return 'External link';
        }

        return match ($this->trust) {
            'official' => 'Official',
            'trusted' => 'Trusted',
            default => 'Unverified',
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->sourceLabel();
    }
}
