<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClipLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_id', 'video_platform', 'video_id', 'url', 'channel_name', 'channel_id',
        'label', 'trust', 'source', 'scene_key', 'status', 'verified_at', 'created_by',
    ];

    protected $appends = [
        'is_embeddable',
        'source_label',
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

    public function sourceLabel(): string
    {
        if ($this->video_platform === 'other' || $this->video_platform === 'external') {
            return 'External link';
        }

        return match ($this->trust) {
            'official' => 'Official',
            'trusted' => 'Trusted',
            default => 'Unverified',
        };
    }

    public function getIsEmbeddableAttribute(): bool
    {
        return $this->isEmbeddable();
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->sourceLabel();
    }
}
