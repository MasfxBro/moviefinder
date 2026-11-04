<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\MediaType;

readonly class TitleData
{
    /**
     * @param  array<string>  $directors
     * @param  array<string>  $cast
     * @param  array<int>  $genreIds
     * @param  array<string>  $videos
     */
    public function __construct(
        public int $tmdbId,
        public MediaType $type,
        public string $title,
        public ?string $originalTitle,
        public ?string $overview,
        public ?int $releaseYear,
        public ?string $posterPath,
        public ?string $backdropPath,
        public ?float $voteAverage,
        public array $directors,
        public array $cast,
        public array $genreIds,
        public array $videos,
        public bool $adult,
        public ?string $tagline = null,
        public ?int $runtimeMinutes = null,
        public ?int $seasonsCount = null,
        public ?int $episodesCount = null,
        public ?string $originalLanguage = null,
        public ?int $voteCount = null,
        public ?float $popularity = null,
    ) {}
}
