<?php

declare(strict_types=1);

namespace App\Services\Tmdb;

use App\DTOs\TitleData;
use App\Enums\MediaType;

class TmdbMapper
{
    private const MAX_DIRECTORS = 3;

    private const MAX_CAST = 8;

    /**
     * @param  array<string, mixed>  $data
     */
    public function mapToTitleData(array $data, MediaType $type): TitleData
    {
        $title = $type === MediaType::Movie
            ? ($data['title'] ?? '')
            : ($data['name'] ?? '');

        $originalTitle = $type === MediaType::Movie
            ? ($data['original_title'] ?? null)
            : ($data['original_name'] ?? null);

        $releaseDate = $type === MediaType::Movie
            ? ($data['release_date'] ?? null)
            : ($data['first_air_date'] ?? null);

        $releaseYear = $releaseDate ? (int) substr($releaseDate, 0, 4) : null;

        $directors = $this->extractDirectors($data, $type);
        $cast = $this->extractCast($data);
        $videos = $this->extractVideos($data);

        $rawGenres = $data['genre_ids'] ?? $data['genres'] ?? [];
        $genreIds = array_map(
            fn (mixed $item) => is_array($item) ? (int) $item['id'] : (int) $item,
            $rawGenres
        );

        return new TitleData(
            tmdbId: (int) $data['id'],
            type: $type,
            title: $title,
            originalTitle: $originalTitle,
            overview: $data['overview'] ?? null,
            releaseYear: $releaseYear,
            posterPath: $data['poster_path'] ?? null,
            backdropPath: $data['backdrop_path'] ?? null,
            voteAverage: isset($data['vote_average']) ? (float) $data['vote_average'] : null,
            directors: $directors,
            cast: $cast,
            genreIds: $genreIds,
            videos: $videos,
            adult: (bool) ($data['adult'] ?? false),
            tagline: $data['tagline'] ?? null,
            runtimeMinutes: isset($data['runtime']) ? (int) $data['runtime'] : null,
            seasonsCount: isset($data['number_of_seasons']) ? (int) $data['number_of_seasons'] : null,
            episodesCount: isset($data['number_of_episodes']) ? (int) $data['number_of_episodes'] : null,
            originalLanguage: $data['original_language'] ?? null,
            voteCount: isset($data['vote_count']) ? (int) $data['vote_count'] : null,
            popularity: isset($data['popularity']) ? (float) $data['popularity'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string>
     */
    private function extractDirectors(array $data, MediaType $type): array
    {
        $credits = $data['credits'] ?? [];

        if ($type === MediaType::Movie) {
            $crew = $credits['crew'] ?? [];
            $directors = array_filter($crew, fn (array $member) => ($member['job'] ?? '') === 'Director');

            return array_slice(
                array_map(fn (array $director) => $director['name'], $directors),
                0,
                self::MAX_DIRECTORS
            );
        }

        // TV shows: created_by
        $creators = $data['created_by'] ?? [];

        return array_slice(
            array_map(fn (array $creator) => $creator['name'], $creators),
            0,
            self::MAX_DIRECTORS
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string>
     */
    private function extractCast(array $data): array
    {
        $credits = $data['credits'] ?? [];
        $cast = $credits['cast'] ?? [];

        return array_slice(
            array_map(fn (array $actor) => $actor['name'], $cast),
            0,
            self::MAX_CAST
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string>
     */
    private function extractVideos(array $data): array
    {
        $videos = $data['videos']['results'] ?? [];

        // Filter only YouTube videos of type Trailer or Teaser
        $youtubeVideos = array_filter(
            $videos,
            fn (array $video) => ($video['site'] ?? '') === 'YouTube'
                && in_array($video['type'] ?? '', ['Trailer', 'Teaser'], true)
        );

        // Prioritize: official Trailer > Trailer > Teaser
        usort($youtubeVideos, function (array $a, array $b) {
            $priority = static fn (array $v): int => match (true) {
                (($v['type'] ?? '') === 'Trailer' && ($v['official'] ?? false)) => 1,
                (($v['type'] ?? '') === 'Trailer') => 2,
                default => 3,
            };

            return $priority($a) <=> $priority($b);
        });

        return array_map(fn (array $video) => $video['key'], $youtubeVideos);
    }
}
