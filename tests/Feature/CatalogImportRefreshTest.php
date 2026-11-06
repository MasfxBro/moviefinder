<?php

declare(strict_types=1);

use App\Contracts\MetadataProvider;
use App\DTOs\TitleData;
use App\Enums\MediaType;
use App\Exceptions\Tmdb\TmdbNotFoundException;
use App\Models\Title;
use App\Services\Catalog\CatalogService;
use App\Services\Catalog\TitleImporter;
use Database\Seeders\GenreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('title_importer:ip:127.0.0.1');
    RateLimiter::clear('title_importer:global');
    $this->seed(GenreSeeder::class);
});

test('import twice creates one row', function () {
    $mockProvider = Mockery::mock(MetadataProvider::class);
    $mockProvider->shouldReceive('details')
        ->with(101, MediaType::Movie)
        ->andReturn(new TitleData(
            tmdbId: 101,
            type: MediaType::Movie,
            title: 'Inception',
            originalTitle: 'Inception',
            overview: 'A mind-bending thriller',
            releaseYear: 2010,
            posterPath: '/poster.jpg',
            backdropPath: '/backdrop.jpg',
            voteAverage: 8.8,
            directors: ['Christopher Nolan'],
            cast: ['Leonardo DiCaprio', 'Joseph Gordon-Levitt'],
            genreIds: [28, 878], // Action, Sci-Fi
            videos: ['trailer_key'],
            adult: false,
        ));

    $importer = new TitleImporter($mockProvider);

    $title1 = $importer->import(101, MediaType::Movie, bypassThrottle: true);
    $title2 = $importer->import(101, MediaType::Movie, bypassThrottle: true);

    expect(Title::where('tmdb_id', 101)->count())->toBe(1)
        ->and($title1->id)->toBe($title2->id)
        ->and($title2->genres)->toHaveCount(2)
        ->and($title2->people)->toHaveCount(3); // 1 director + 2 cast
});

test('adult refused', function () {
    $mockProvider = Mockery::mock(MetadataProvider::class);
    $mockProvider->shouldReceive('details')
        ->with(999, MediaType::Movie)
        ->andReturn(new TitleData(
            tmdbId: 999,
            type: MediaType::Movie,
            title: 'Adult Content',
            originalTitle: 'Adult Content',
            overview: 'Not allowed',
            releaseYear: 2020,
            posterPath: null,
            backdropPath: null,
            voteAverage: 5.0,
            directors: [],
            cast: [],
            genreIds: [],
            videos: [],
            adult: true,
        ));

    $importer = new TitleImporter($mockProvider);

    expect(fn () => $importer->import(999, MediaType::Movie, bypassThrottle: true))
        ->toThrow(InvalidArgumentException::class, 'Adult titles are refused.');

    expect(Title::where('tmdb_id', 999)->count())->toBe(0);
});

test('refresh respects locked_fields', function () {
    $title = Title::factory()->create([
        'tmdb_id' => 202,
        'source' => 'tmdb',
        'media_type' => 'movie',
        'title' => 'Original Admin Title',
        'overview' => 'Original Overview',
        'locked_fields' => ['title'],
        'synced_at' => now()->subDays(151),
    ]);

    $mockProvider = Mockery::mock(MetadataProvider::class);
    $mockProvider->shouldReceive('refreshDetails')
        ->with(202, MediaType::Movie)
        ->andReturn(new TitleData(
            tmdbId: 202,
            type: MediaType::Movie,
            title: 'New TMDB Title',
            originalTitle: 'Original Title',
            overview: 'Updated Overview from TMDB',
            releaseYear: 2022,
            posterPath: '/new_poster.jpg',
            backdropPath: '/new_backdrop.jpg',
            voteAverage: 7.5,
            directors: [],
            cast: [],
            genreIds: [],
            videos: [],
            adult: false,
        ));

    $importer = new TitleImporter($mockProvider);
    $catalogService = new CatalogService($importer, $mockProvider);

    $count = $catalogService->refresh(limit: 100);

    $refreshedTitle = $title->fresh();

    expect($count)->toBe(1)
        ->and($refreshedTitle->title)->toBe('Original Admin Title') // locked field preserved!
        ->and($refreshedTitle->overview)->toBe('Updated Overview from TMDB') // updated!
        ->and($refreshedTitle->synced_at->isToday())->toBeTrue();
});

test('404 hides the title', function () {
    $title = Title::factory()->create([
        'tmdb_id' => 404001,
        'source' => 'tmdb',
        'media_type' => 'movie',
        'title' => 'Deleted Movie',
        'is_hidden' => false,
        'synced_at' => now()->subDays(151),
    ]);

    $mockProvider = Mockery::mock(MetadataProvider::class);
    $mockProvider->shouldReceive('refreshDetails')
        ->with(404001, MediaType::Movie)
        ->andThrow(new TmdbNotFoundException('Not found on TMDB'));

    $importer = new TitleImporter($mockProvider);
    $catalogService = new CatalogService($importer, $mockProvider);

    $count = $catalogService->refresh(limit: 100);

    expect($count)->toBe(1)
        ->and($title->fresh()->is_hidden)->toBeTrue();
});

test('importer throttles requests', function () {
    $mockProvider = Mockery::mock(MetadataProvider::class);
    $mockProvider->shouldReceive('details')
        ->andReturnUsing(fn (int $id): TitleData => new TitleData(
            tmdbId: $id,
            type: MediaType::Movie,
            title: 'Sample',
            originalTitle: 'Sample',
            overview: 'Sample',
            releaseYear: 2020,
            posterPath: null,
            backdropPath: null,
            voteAverage: 6.0,
            directors: [],
            cast: [],
            genreIds: [],
            videos: [],
            adult: false,
        ));

    $importer = new TitleImporter($mockProvider);

    for ($i = 0; $i < 30; $i++) {
        $importer->import(1000 + $i, MediaType::Movie);
    }

    expect(fn () => $importer->import(9999, MediaType::Movie))
        ->toThrow(TooManyRequestsHttpException::class, 'IP import rate limit exceeded.');
});

test('catalog seed command runs', function () {
    $this->instance(
        MetadataProvider::class,
        $mockProvider = Mockery::mock(MetadataProvider::class)
    );

    $mockProvider->shouldReceive('popular')
        ->with(MediaType::Movie)
        ->andReturn([
            new TitleData(
                tmdbId: 501,
                type: MediaType::Movie,
                title: 'Popular Movie',
                originalTitle: 'Popular Movie',
                overview: 'Overview',
                releaseYear: 2023,
                posterPath: null,
                backdropPath: null,
                voteAverage: 8.0,
                directors: [],
                cast: [],
                genreIds: [],
                videos: [],
                adult: false,
            ),
        ]);

    $mockProvider->shouldReceive('popular')
        ->with(MediaType::Tv)
        ->andReturn([]);

    $mockProvider->shouldReceive('details')
        ->with(501, MediaType::Movie)
        ->andReturn(new TitleData(
            tmdbId: 501,
            type: MediaType::Movie,
            title: 'Popular Movie Details',
            originalTitle: 'Popular Movie',
            overview: 'Overview',
            releaseYear: 2023,
            posterPath: null,
            backdropPath: null,
            voteAverage: 8.0,
            directors: [],
            cast: [],
            genreIds: [],
            videos: [],
            adult: false,
        ));

    Artisan::call('catalog:seed', ['--limit' => 10]);

    expect(Title::where('tmdb_id', 501)->count())->toBe(1);
});
