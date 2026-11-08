<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    protected $table = 'announcements';

    protected $fillable = [
        'kind',
        'message',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('announcements.active'));
        static::deleted(fn () => Cache::forget('announcements.active'));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to active announcements within their time window.
     */
    public function scopeActive(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $time = $at ?? now();

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($time) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $time);
            })
            ->where(function (Builder $q) use ($time) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $time);
            });
    }

    /**
     * Return currently active announcements through 60s cache.
     *
     * @return Collection<int, Announcement>
     */
    public static function currentActive(): Collection
    {
        return Cache::remember('announcements.active', 60, function (): Collection {
            return self::query()
                ->active()
                ->orderBy('id', 'desc')
                ->get();
        });
    }
}
