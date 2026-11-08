<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Contracts\MetadataProvider;
use App\Enums\MediaType;
use App\Exceptions\Tmdb\TmdbNotFoundException;
use App\Models\ClipLink;
use App\Models\Title;
use App\Models\TitleRequest;
use App\Models\User;
use App\Models\Watchlist;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CatalogService
{
    private readonly PosterService $posterService;

    public function __construct(
        private readonly TitleImporter $importer,
        private readonly MetadataProvider $metadataProvider,
        ?PosterService $posterService = null
    ) {
        $this->posterService = $posterService ?? app(PosterService::class);
    }

    public function import(int|string $tmdbId, MediaType|string $type, bool $bypassThrottle = false): Title
    {
        $title = $this->importer->import($tmdbId, $type, $bypassThrottle);
        $this->invalidateCacheForTitle($title);

        return $title;
    }

    public function seedPopular(int $limitPerType = 20): int
    {
        $limitPerType = $this->validatedLimit($limitPerType);
        $count = 0;

        foreach ([MediaType::Movie, MediaType::Tv] as $type) {
            foreach (array_slice($this->metadataProvider->popular($type), 0, $limitPerType) as $item) {
                try {
                    $this->importer->import($item->tmdbId, $type, bypassThrottle: true);
                    $count++;
                } catch (\Throwable $exception) {
                    Log::warning('Catalog seed import failed.', [
                        'tmdb_id' => $item->tmdbId,
                        'media_type' => $type->value,
                        'exception' => $exception::class,
                    ]);
                }
            }
        }

        if ($count > 0) {
            $this->invalidateCacheForTitle();
        }

        return $count;
    }

    public function refresh(int $limit = 100): int
    {
        $limit = $this->validatedLimit($limit);
        $ageDays = (int) config('catalog.max_refresh_age_days', 150);
        $titles = Title::query()
            ->where('source', 'tmdb')
            ->where(function ($query) use ($ageDays): void {
                $query->whereNull('synced_at')->orWhere('synced_at', '<=', now()->subDays($ageDays));
            })
            ->orderByRaw('synced_at IS NULL DESC')
            ->orderBy('synced_at')
            ->limit($limit)
            ->get(['id', 'tmdb_id', 'media_type']);

        $count = 0;
        foreach ($titles as $title) {
            if ($title->tmdb_id === null) {
                continue;
            }

            try {
                $this->importer->import(
                    $title->tmdb_id,
                    MediaType::from($title->media_type),
                    bypassThrottle: true,
                    forceRefresh: true,
                );
                $count++;
            } catch (TmdbNotFoundException $exception) {
                $title->is_hidden = true;
                $title->save();
                $count++;
            } catch (\Throwable $exception) {
                Log::warning('Catalog refresh failed.', [
                    'title_id' => $title->id,
                    'tmdb_id' => $title->tmdb_id,
                    'media_type' => $title->media_type,
                    'exception' => $exception::class,
                ]);
            }
        }

        if ($count > 0) {
            $this->invalidateCacheForTitle();
        }

        return $count;
    }

    public function refreshTitle(Title $title): Title
    {
        if ($title->source !== 'tmdb' || ! $title->tmdb_id) {
            throw new \InvalidArgumentException('Only TMDB titles can be refreshed.');
        }

        try {
            $refreshed = $this->importer->import(
                $title->tmdb_id,
                MediaType::from($title->media_type),
                bypassThrottle: true,
                forceRefresh: true,
            );
            $this->invalidateCacheForTitle($refreshed);

            return $refreshed;
        } catch (TmdbNotFoundException) {
            $title->is_hidden = true;
            $title->save();
            $this->invalidateCacheForTitle($title);

            return $title;
        }
    }

    /**
     * Create manual title with poster upload.
     */
    public function createManual(array $attributes, ?UploadedFile $poster = null, ?User $creator = null): Title
    {
        $posterPath = null;
        if ($poster !== null) {
            $posterPath = $this->posterService->processAndStore($poster);
        }

        $genreIds = $attributes['genres'] ?? [];
        unset($attributes['genres'], $attributes['poster']);

        $attributes['source'] = 'manual';
        $attributes['tmdb_id'] = null;
        $attributes['synced_at'] = null;
        $attributes['adult'] = false;
        $attributes['created_by'] = $creator?->id;
        if ($posterPath !== null) {
            $attributes['poster_path'] = $posterPath;
        }

        $title = Title::create($attributes);

        if (! empty($genreIds)) {
            $title->genres()->sync($genreIds);
        }

        $this->invalidateCacheForTitle($title);

        return $title->fresh(['genres', 'people']);
    }

    /**
     * Update title attributes and record edited fields in locked_fields.
     */
    public function update(Title $title, array $attributes, ?UploadedFile $poster = null): Title
    {
        if ($poster !== null) {
            $newPoster = $this->posterService->processAndStore($poster);
            $this->posterService->deleteIfLocal($title->poster_path);
            $attributes['poster_path'] = $newPoster;
        }

        $genreIds = $attributes['genres'] ?? null;
        unset($attributes['genres'], $attributes['poster']);

        $lockable = [
            'title', 'original_title', 'overview', 'tagline',
            'release_year', 'runtime_minutes', 'seasons_count', 'episodes_count',
            'original_language', 'poster_path', 'backdrop_path', 'trailer_youtube_key',
            'vote_average', 'vote_count', 'popularity',
        ];

        $existingLocked = $title->locked_fields ?? [];
        $modifiedFields = [];
        foreach ($lockable as $field) {
            if (array_key_exists($field, $attributes) && (string) $title->{$field} !== (string) $attributes[$field]) {
                $modifiedFields[] = $field;
            }
        }

        if (isset($attributes['locked_fields']) && is_array($attributes['locked_fields'])) {
            $attributes['locked_fields'] = array_values(array_unique(array_merge($attributes['locked_fields'], $modifiedFields)));
        } else {
            $merged = array_values(array_unique(array_merge($existingLocked, $modifiedFields)));
            $attributes['locked_fields'] = empty($merged) ? null : $merged;
        }

        $title->fill($attributes);
        $title->save();

        if ($genreIds !== null) {
            $title->genres()->sync($genreIds);
        }

        $this->invalidateCacheForTitle($title);

        return $title->fresh(['genres', 'people']);
    }

    public function toggleHide(Title $title): Title
    {
        $title->is_hidden = ! $title->is_hidden;
        $title->save();
        $this->invalidateCacheForTitle($title);

        return $title;
    }

    /**
     * Return summary of entities depending on this title.
     *
     * @return array<string, int>
     */
    public function dependencySummary(Title $title): array
    {
        return [
            'watchlists' => Watchlist::where('title_id', $title->id)->count(),
            'clip_links' => ClipLink::where('title_id', $title->id)->count(),
            'title_requests' => TitleRequest::where('resolved_title_id', $title->id)->count(),
            'ai_usage_logs' => Schema::hasTable('ai_usage_log')
                ? DB::table('ai_usage_log')->where('title_id', $title->id)->count()
                : 0,
            'genres' => $title->genres()->count(),
            'people' => $title->people()->count(),
        ];
    }

    /**
     * Delete title and clean up disk poster.
     *
     * @return array<string, int> Dependency summary before deletion.
     */
    public function delete(Title $title): array
    {
        $summary = $this->dependencySummary($title);

        $this->posterService->deleteIfLocal($title->poster_path);

        $title->delete();
        $this->invalidateCacheForTitle($title);

        return $summary;
    }

    /**
     * Bulk import TMDB titles by ID and type.
     *
     * @param  array<int, array{id: int|string, type: MediaType|string}>  $items
     * @return array{imported: array<int, Title>, failed: array<int, array{id: int|string, error: string}>}
     */
    public function bulkImport(array $items, bool $bypassThrottle = true): array
    {
        $imported = [];
        $failed = [];

        foreach ($items as $item) {
            $tmdbId = $item['id'];
            $type = $item['type'] ?? MediaType::Movie;
            try {
                $title = $this->importer->import($tmdbId, $type, bypassThrottle: $bypassThrottle);
                $imported[] = $title;
            } catch (\Throwable $e) {
                $failed[] = ['id' => $tmdbId, 'error' => $e->getMessage()];
            }
        }

        if (! empty($imported)) {
            $this->invalidateCacheForTitle();
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
        ];
    }

    /**
     * Invalidate catalog and detail cache keys.
     */
    public function invalidateCacheForTitle(?Title $title = null): void
    {
        Cache::forget('v1:landing:posters');
        Cache::forget('v1:home:trending');
        Cache::forget('v1:genres:map');

        if ($title !== null && $title->tmdb_id !== null) {
            Cache::forget("v1:tmdb:details:{$title->tmdb_id}:{$title->media_type}");
            Cache::forget("v1:tmdb:watch_providers:{$title->tmdb_id}:{$title->media_type}:ID");
        }
    }

    private function validatedLimit(int $limit): int
    {
        $maximum = (int) config('catalog.max_command_limit', 500);
        if ($limit < 1 || $limit > $maximum) {
            throw new \InvalidArgumentException("Catalog limit must be between 1 and {$maximum}.");
        }

        return $limit;
    }
}
