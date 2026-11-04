<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Contracts\MetadataProvider;
use App\Enums\MediaType;
use App\Exceptions\Tmdb\TmdbNotFoundException;
use App\Models\Title;

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
        $count = 0;

        foreach ([MediaType::Movie, MediaType::Tv] as $type) {
            $popular = $this->metadataProvider->popular($type);
            $items = array_slice($popular, 0, $limitPerType);

            foreach ($items as $item) {
                try {
                    $this->importer->import($item->tmdbId, $type, bypassThrottle: true);
                    $count++;
                } catch (\Throwable $e) {
                    // Skip invalid or adult titles during seed
                    continue;
                }
            }
        }

        return $count;
    }

    public function refresh(int $limit = 100): int
    {
        $titles = Title::where('source', 'tmdb')
            ->orderByRaw('synced_at IS NULL DESC')
            ->orderBy('synced_at', 'asc')
            ->limit($limit)
            ->get();

        $count = 0;

        foreach ($titles as $title) {
            if ($title->tmdb_id === null) {
                continue;
            }

            try {
                $this->importer->import(
                    $title->tmdb_id,
                    MediaType::from($title->media_type),
                    bypassThrottle: true
                );
                $count++;
            } catch (TmdbNotFoundException $e) {
                $title->is_hidden = true;
                $title->save();
                $count++;
            } catch (\Throwable $e) {
                // Continue on other errors
                continue;
            }
        }

        return $count;
    }
}
