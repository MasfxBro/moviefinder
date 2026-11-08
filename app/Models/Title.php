<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property array<string>|null $locked_fields
 */
class Title extends Model
{
    use HasFactory;

    protected $fillable = [
        'tmdb_id',
        'source',
        'media_type',
        'slug',
        'title',
        'original_title',
        'release_year',
        'poster_path',
        'backdrop_path',
        'overview',
        'tagline',
        'runtime_minutes',
        'seasons_count',
        'episodes_count',
        'original_language',
        'vote_average',
        'vote_count',
        'popularity',
        'trailer_youtube_key',
        'is_hidden',
        'synced_at',
        'locked_fields',
        'last_viewed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'adult' => 'boolean',
            'is_hidden' => 'boolean',
            'synced_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'locked_fields' => 'array',
            'vote_average' => 'decimal:1',
            'popularity' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Title $title) {
            if ($title->adult) {
                throw new \RuntimeException('Adult titles cannot be created');
            }

            if (empty($title->slug)) {
                $title->slug = static::generateUniqueSlug($title->title, $title->release_year);
            }
        });

        static::updating(function (Title $title) {
            if ($title->isDirty('adult') && $title->adult) {
                throw new \RuntimeException('Adult flag cannot be set to true');
            }
        });
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'title_genre');
    }

    public function people(): HasMany
    {
        return $this->hasMany(TitlePerson::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPosterUrlAttribute(): ?string
    {
        if (! $this->poster_path) {
            return null;
        }

        if (str_starts_with($this->poster_path, 'local:')) {
            return Storage::disk('public')->url(substr($this->poster_path, 6));
        }

        $imageBase = config('services.tmdb.image_base', 'https://image.tmdb.org/t/p/');

        return $imageBase.'w500'.$this->poster_path;
    }

    public static function generateUniqueSlug(string $title, ?int $year = null): string
    {
        $base = Str::slug($title);
        $suffix = $year ? "-{$year}" : '';
        $slug = $base.$suffix;

        $count = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.$suffix."-{$count}";
            $count++;
        }

        return $slug;
    }
}
