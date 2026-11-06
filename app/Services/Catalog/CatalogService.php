<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Contracts\MetadataProvider;
use App\Enums\MediaType;
use App\Exceptions\Tmdb\TmdbNotFoundException;
use App\Models\Title;
use Illuminate\Support\Facades\Log;

class CatalogService
{
    public function __construct(
        private readonly TitleImporter $importer,
        private readonly MetadataProvider $metadataProvider
    ) {}

    public function import(int|string $tmdbId, MediaType|string $type, bool $bypassThrottle = false): Title
    {
        return $this->importer->import($tmdbId, $type, $bypassThrottle);
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

        return $count;
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
