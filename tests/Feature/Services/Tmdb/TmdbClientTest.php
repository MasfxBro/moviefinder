<?php

declare(strict_types=1);

use App\Enums\MediaType;
use App\Exceptions\Tmdb\TmdbConnectionException;
use App\Exceptions\Tmdb\TmdbNotFoundException;
use App\Services\Tmdb\TmdbClient;
use App\Services\Tmdb\TmdbMapper;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    $this->mapper = new TmdbMapper;
    $this->client = new TmdbClient('test-token', $this->mapper);
});

test('search filters adult content', function () {
    Http::fake([
        'api.themoviedb.org/3/search/movie*' => Http::response([
            'results' => [
                [
                    'id' => 1,
                    'title' => 'Clean Movie',
                    'adult' => false,
                ],
                [
                    'id' => 2,
                    'title' => 'Adult Movie',
                    'adult' => true,
                ],
                [
                    'id' => 3,
                    'title' => 'Another Clean',
                    'adult' => false,
                ],
            ],
        ]),
    ]);

    $results = $this->client->search('test', MediaType::Movie);

    expect($results)->toHaveCount(2)
        ->and($results[0]->tmdbId)->toBe(1)
        ->and($results[1]->tmdbId)->toBe(3);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'include_adult=false');
    });
});

test('search results are cached', function () {
    Http::fake([
        'api.themoviedb.org/3/search/movie*' => Http::response([
            'results' => [
                ['id' => 1, 'title' => 'Movie', 'adult' => false],
            ],
        ]),
    ]);

    $this->client->search('test', MediaType::Movie);
    $this->client->search('test', MediaType::Movie);

    Http::assertSentCount(1);
});

test('details includes credits and videos', function () {
    Http::fake([
        '*' => Http::response([
            'id' => 123,
            'title' => 'Test Movie',
            'adult' => false,
            'credits' => [
                'crew' => [['name' => 'Director', 'job' => 'Director']],
                'cast' => [['name' => 'Actor']],
            ],
            'videos' => [
                'results' => [['key' => 'abc', 'site' => 'YouTube']],
            ],
        ]),
    ]);

    $result = $this->client->details(123, MediaType::Movie);

    expect($result->directors)->toBe(['Director'])
        ->and($result->cast)->toBe(['Actor'])
        ->and($result->videos)->toBe(['abc']);
});

test('handles 404 errors', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response(null, 404),
    ]);

    $this->client->details(999, MediaType::Movie);
})->throws(TmdbNotFoundException::class);

test('handles connection timeout with retry', function () {
    Http::fake(function () {
        throw new ConnectionException('Timeout');
    });

    $this->client->search('test', MediaType::Movie);
})->throws(TmdbConnectionException::class, 'Connection failed after retries');

test('trending filters adult content', function () {
    Http::fake([
        'api.themoviedb.org/3/trending/movie/*' => Http::response([
            'results' => [
                ['id' => 1, 'title' => 'Movie 1', 'adult' => false],
                ['id' => 2, 'title' => 'Adult', 'adult' => true],
            ],
        ]),
    ]);

    $results = $this->client->trending(MediaType::Movie);

    expect($results)->toHaveCount(1)
        ->and($results[0]->tmdbId)->toBe(1);
});

test('popular filters adult content', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/popular*' => Http::response([
            'results' => [
                ['id' => 10, 'title' => 'Popular', 'adult' => false],
            ],
        ]),
    ]);

    $results = $this->client->popular(MediaType::Movie);

    expect($results)->toHaveCount(1);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'include_adult=false');
    });
});

test('topRated filters adult content', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/top_rated*' => Http::response([
            'results' => [
                ['id' => 20, 'title' => 'Top Rated', 'adult' => false],
            ],
        ]),
    ]);

    $results = $this->client->topRated(MediaType::Movie);

    expect($results)->toHaveCount(1);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'include_adult=false');
    });
});

test('genres returns mapped array', function () {
    Http::fake([
        'api.themoviedb.org/3/genre/movie/list*' => Http::response([
            'genres' => [
                ['id' => 28, 'name' => 'Action'],
                ['id' => 12, 'name' => 'Adventure'],
            ],
        ]),
    ]);

    $genres = $this->client->genres(MediaType::Movie);

    expect($genres)->toBe([
        '28' => 'Action',
        '12' => 'Adventure',
    ]);
});

test('watchProviders returns region data', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/123/watch/providers*' => Http::response([
            'results' => [
                'ID' => [
                    'flatrate' => [
                        [
                            'provider_id' => 8,
                            'logo_path' => '/netflix.jpg',
                            'provider_name' => 'Netflix',
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $providers = $this->client->watchProviders(123, MediaType::Movie, 'ID');

    expect($providers)->toBe([
        '8' => [
            'logo_path' => '/netflix.jpg',
            'provider_name' => 'Netflix',
        ],
    ]);
});

test('watchProviders caches results', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response(['results' => []]),
    ]);

    $this->client->watchProviders(123, MediaType::Movie, 'ID');
    $this->client->watchProviders(123, MediaType::Movie, 'ID');

    Http::assertSentCount(1);
});
