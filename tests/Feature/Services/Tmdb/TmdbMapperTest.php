<?php

declare(strict_types=1);

use App\Enums\MediaType;
use App\Services\Tmdb\TmdbMapper;

test('maps movie data correctly', function () {
    $mapper = new TmdbMapper;

    $data = [
        'id' => 123,
        'title' => 'Test Movie',
        'original_title' => 'Original Test',
        'overview' => 'A test movie',
        'release_date' => '2023-05-15',
        'poster_path' => '/poster.jpg',
        'backdrop_path' => '/backdrop.jpg',
        'vote_average' => 8.5,
        'adult' => false,
        'genre_ids' => [28, 12],
        'credits' => [
            'crew' => [
                ['name' => 'Director 1', 'job' => 'Director'],
                ['name' => 'Director 2', 'job' => 'Director'],
                ['name' => 'Director 3', 'job' => 'Director'],
                ['name' => 'Director 4', 'job' => 'Director'],
            ],
            'cast' => [
                ['name' => 'Actor 1'],
                ['name' => 'Actor 2'],
                ['name' => 'Actor 3'],
                ['name' => 'Actor 4'],
                ['name' => 'Actor 5'],
                ['name' => 'Actor 6'],
                ['name' => 'Actor 7'],
                ['name' => 'Actor 8'],
                ['name' => 'Actor 9'],
            ],
        ],
        'videos' => [
            'results' => [
                ['key' => 'abc123', 'site' => 'YouTube'],
                ['key' => 'xyz789', 'site' => 'YouTube'],
                ['key' => 'ignored', 'site' => 'Vimeo'],
            ],
        ],
    ];

    $result = $mapper->mapToTitleData($data, MediaType::Movie);

    expect($result->tmdbId)->toBe(123)
        ->and($result->type)->toBe(MediaType::Movie)
        ->and($result->title)->toBe('Test Movie')
        ->and($result->originalTitle)->toBe('Original Test')
        ->and($result->overview)->toBe('A test movie')
        ->and($result->releaseYear)->toBe(2023)
        ->and($result->posterPath)->toBe('/poster.jpg')
        ->and($result->backdropPath)->toBe('/backdrop.jpg')
        ->and($result->voteAverage)->toBe(8.5)
        ->and($result->adult)->toBeFalse()
        ->and($result->directors)->toHaveCount(3)
        ->and($result->directors)->toBe(['Director 1', 'Director 2', 'Director 3'])
        ->and($result->cast)->toHaveCount(8)
        ->and($result->cast[0])->toBe('Actor 1')
        ->and($result->cast[7])->toBe('Actor 8')
        ->and($result->genreIds)->toBe([28, 12])
        ->and($result->videos)->toHaveCount(2)
        ->and($result->videos)->toBe(['abc123', 'xyz789']);
});

test('maps tv show data correctly', function () {
    $mapper = new TmdbMapper;

    $data = [
        'id' => 456,
        'name' => 'Test TV Show',
        'original_name' => 'Original TV',
        'overview' => 'A test TV show',
        'first_air_date' => '2022-03-10',
        'poster_path' => '/tv_poster.jpg',
        'adult' => false,
        'genre_ids' => [18],
        'created_by' => [
            ['name' => 'Creator 1'],
            ['name' => 'Creator 2'],
            ['name' => 'Creator 3'],
            ['name' => 'Creator 4'],
        ],
        'credits' => [
            'cast' => [
                ['name' => 'TV Actor 1'],
                ['name' => 'TV Actor 2'],
            ],
        ],
        'videos' => [
            'results' => [],
        ],
    ];

    $result = $mapper->mapToTitleData($data, MediaType::Tv);

    expect($result->tmdbId)->toBe(456)
        ->and($result->type)->toBe(MediaType::Tv)
        ->and($result->title)->toBe('Test TV Show')
        ->and($result->originalTitle)->toBe('Original TV')
        ->and($result->releaseYear)->toBe(2022)
        ->and($result->directors)->toHaveCount(3)
        ->and($result->directors)->toBe(['Creator 1', 'Creator 2', 'Creator 3'])
        ->and($result->cast)->toBe(['TV Actor 1', 'TV Actor 2'])
        ->and($result->videos)->toBeEmpty();
});

test('drops adult content', function () {
    $mapper = new TmdbMapper;

    $data = [
        'id' => 999,
        'title' => 'Adult Movie',
        'adult' => true,
    ];

    $result = $mapper->mapToTitleData($data, MediaType::Movie);

    expect($result->adult)->toBeTrue();
});

test('handles missing optional fields', function () {
    $mapper = new TmdbMapper;

    $data = [
        'id' => 111,
        'title' => 'Minimal Movie',
    ];

    $result = $mapper->mapToTitleData($data, MediaType::Movie);

    expect($result->tmdbId)->toBe(111)
        ->and($result->title)->toBe('Minimal Movie')
        ->and($result->originalTitle)->toBeNull()
        ->and($result->overview)->toBeNull()
        ->and($result->releaseYear)->toBeNull()
        ->and($result->posterPath)->toBeNull()
        ->and($result->backdropPath)->toBeNull()
        ->and($result->voteAverage)->toBeNull()
        ->and($result->directors)->toBeEmpty()
        ->and($result->cast)->toBeEmpty()
        ->and($result->genreIds)->toBeEmpty()
        ->and($result->videos)->toBeEmpty()
        ->and($result->adult)->toBeFalse();
});
