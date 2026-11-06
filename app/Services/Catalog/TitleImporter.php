<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Contracts\MetadataProvider;
use App\DTOs\TitleData;
use App\Enums\MediaType;
use App\Models\Genre;
use App\Models\Title;
use App\Models\TitlePerson;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class TitleImporter
{
    public function __construct(
        private readonly MetadataProvider $metadataProvider
    ) {}

    public function import(int|string $tmdbId, MediaType|string $type, bool $bypassThrottle = false, bool $forceRefresh = false): Title
    {
        if (! ctype_digit((string) $tmdbId) || (int) $tmdbId < 1) {
            throw new \InvalidArgumentException('TMDB ID must be a positive integer.');
        }

        $tmdbIdInt = (int) $tmdbId;
        $mediaType = $type instanceof MediaType ? $type : MediaType::from($type);

        if (! $bypassThrottle) {
            $ip = request()->ip() ?? '127.0.0.1';

            $ipLimit = (int) config('catalog.import_throttle_per_hour', 30);
            $globalLimit = (int) config('catalog.global_import_throttle_per_hour', 200);
            if (RateLimiter::tooManyAttempts('title_importer:ip:'.$ip, $ipLimit)) {
                throw new TooManyRequestsHttpException(RateLimiter::availableIn('title_importer:ip:'.$ip), 'IP import rate limit exceeded.');
            }

            if (RateLimiter::tooManyAttempts('title_importer:global', $globalLimit)) {
                throw new TooManyRequestsHttpException(RateLimiter::availableIn('title_importer:global'), 'Global import rate limit exceeded.');
            }

            RateLimiter::hit('title_importer:ip:'.$ip, 3600);
            RateLimiter::hit('title_importer:global', 3600);
        }

        $titleData = $forceRefresh
            ? $this->metadataProvider->refreshDetails($tmdbIdInt, $mediaType)
            : $this->metadataProvider->details($tmdbIdInt, $mediaType);

        if ($titleData->tmdbId !== $tmdbIdInt || $titleData->type !== $mediaType) {
            throw new \UnexpectedValueException('TMDB response does not match the requested title.');
        }

        if ($titleData->adult) {
            throw new \InvalidArgumentException('Adult titles are refused.');
        }

        try {
            return DB::transaction(fn (): Title => $this->persist($titleData, $mediaType));
        } catch (QueryException $exception) {
            $existing = Title::query()
                ->where('tmdb_id', $tmdbIdInt)
                ->where('media_type', $mediaType->value)
                ->first();

            if ($existing === null) {
                throw $exception;
            }

            return DB::transaction(fn (): Title => $this->persist($titleData, $mediaType));
        }
    }

    private function persist(TitleData $titleData, MediaType $mediaType): Title
    {
        $title = Title::query()
            ->where('tmdb_id', $titleData->tmdbId)
            ->where('media_type', $mediaType->value)
            ->lockForUpdate()
            ->first();

        $attributes = [
            'tmdb_id' => $titleData->tmdbId,
            'source' => 'tmdb',
            'media_type' => $mediaType->value,
            'title' => $titleData->title,
            'original_title' => $titleData->originalTitle,
            'release_year' => $titleData->releaseYear,
            'poster_path' => $titleData->posterPath,
            'backdrop_path' => $titleData->backdropPath,
            'overview' => $titleData->overview,
            'tagline' => $titleData->tagline,
            'runtime_minutes' => $titleData->runtimeMinutes,
            'seasons_count' => $titleData->seasonsCount,
            'episodes_count' => $titleData->episodesCount,
            'original_language' => $titleData->originalLanguage,
            'vote_average' => $titleData->voteAverage ?? 0.0,
            'vote_count' => $titleData->voteCount ?? 0,
            'popularity' => $titleData->popularity ?? 0.0,
            'trailer_youtube_key' => $titleData->videos[0] ?? null,
            'is_hidden' => false,
            'synced_at' => now(),
        ];

        if ($title !== null) {
            foreach ($title->locked_fields ?? [] as $field) {
                unset($attributes[$field]);
            }

            $title->fill($attributes);
            $title->save();
        } else {
            $attributes['slug'] = Title::generateUniqueSlug($titleData->title, $titleData->releaseYear);
            $title = Title::create($attributes);
        }

        $genreIds = $titleData->genreIds === []
            ? []
            : Genre::query()->whereIn('id', $titleData->genreIds)->pluck('id')->all();
        $title->genres()->sync($genreIds);
        $title->people()->delete();

        $role = $mediaType === MediaType::Movie ? 'director' : 'creator';
        $people = [];
        foreach (array_slice($titleData->directors, 0, 3) as $index => $name) {
            $people[] = ['title_id' => $title->id, 'name' => $name, 'role' => $role, 'position' => $index + 1];
        }
        foreach (array_slice($titleData->cast, 0, 8) as $index => $name) {
            $people[] = ['title_id' => $title->id, 'name' => $name, 'role' => 'cast', 'position' => $index + 1];
        }
        if ($people !== []) {
            TitlePerson::query()->insert($people);
        }

        return $title->fresh(['genres', 'people']);
    }
}
