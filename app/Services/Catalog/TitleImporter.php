<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Contracts\MetadataProvider;
use App\Enums\MediaType;
use App\Models\Genre;
use App\Models\Title;
use App\Models\TitlePerson;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class TitleImporter
{
    public function __construct(
        private readonly MetadataProvider $metadataProvider
    ) {}

    public function import(int|string $tmdbId, MediaType|string $type, bool $bypassThrottle = false): Title
    {
        $tmdbIdInt = (int) $tmdbId;
        $mediaType = $type instanceof MediaType ? $type : MediaType::from($type);

        if (! $bypassThrottle) {
            $ip = request()->ip() ?? '127.0.0.1';

            if (RateLimiter::tooManyAttempts('title_importer:ip:'.$ip, 30)) {
                throw new TooManyRequestsHttpException(3600, 'IP import rate limit exceeded.');
            }

            if (RateLimiter::tooManyAttempts('title_importer:global', 200)) {
                throw new TooManyRequestsHttpException(3600, 'Global import rate limit exceeded.');
            }

            RateLimiter::hit('title_importer:ip:'.$ip, 3600);
            RateLimiter::hit('title_importer:global', 3600);
        }

        $titleData = $this->metadataProvider->details($tmdbIdInt, $mediaType);

        if ($titleData->adult) {
            throw new \InvalidArgumentException('Adult titles are refused.');
        }

        $title = Title::where('tmdb_id', $tmdbIdInt)
            ->where('media_type', $mediaType->value)
            ->first();

        $attributes = [
            'tmdb_id' => $tmdbIdInt,
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
            $lockedFields = $title->locked_fields ?? [];
            foreach ($lockedFields as $field) {
                unset($attributes[$field]);
            }

            $title->fill($attributes);
            $title->save();
        } else {
            $attributes['slug'] = Title::generateUniqueSlug($titleData->title, $titleData->releaseYear);
            $title = Title::create($attributes);
        }

        if (! empty($titleData->genreIds)) {
            $validGenreIds = Genre::whereIn('id', $titleData->genreIds)->pluck('id')->all();
            $title->genres()->sync($validGenreIds);
        }

        $title->people()->delete();

        $directorRole = $mediaType === MediaType::Movie ? 'director' : 'creator';
        foreach (array_slice($titleData->directors, 0, 3) as $index => $name) {
            TitlePerson::create([
                'title_id' => $title->id,
                'name' => $name,
                'role' => $directorRole,
                'position' => $index + 1,
            ]);
        }

        foreach (array_slice($titleData->cast, 0, 8) as $index => $name) {
            TitlePerson::create([
                'title_id' => $title->id,
                'name' => $name,
                'role' => 'cast',
                'position' => $index + 1,
            ]);
        }

        return $title->fresh(['genres', 'people']);
    }
}
