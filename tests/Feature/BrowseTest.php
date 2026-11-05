<?php

declare(strict_types=1);

use App\Models\Genre;
use App\Models\Title;
use App\Models\TitlePerson;
use App\Models\User;
use Database\Seeders\GenreSeeder;

beforeEach(function () {
    $this->seed(GenreSeeder::class);
    $this->user = User::factory()->create();
});

function attachDirector(Title $title, string $name): void
{
    TitlePerson::create([
        'title_id' => $title->id,
        'name' => $name,
        'role' => 'director',
        'position' => 1,
    ]);
}

test('guest is redirected to login from browse', function () {
    $this->get('/browse')->assertRedirect(route('login'));
});

test('filters combine with AND across type, genres, year, director and rating', function () {
    $action = Genre::find(28);
    $comedy = Genre::find(35);

    $match = Title::factory()->movie()->create([
        'title' => 'Matching Movie',
        'release_year' => 2005,
        'vote_average' => 8.5,
    ]);
    $match->genres()->attach([$action->id, $comedy->id]);
    attachDirector($match, 'Christopher Nolan');

    $wrongType = Title::factory()->tv()->create([
        'title' => 'Wrong Type Show',
        'release_year' => 2005,
        'vote_average' => 8.5,
    ]);
    $wrongType->genres()->attach([$action->id, $comedy->id]);
    attachDirector($wrongType, 'Christopher Nolan');

    $wrongGenre = Title::factory()->movie()->create([
        'title' => 'Wrong Genre Movie',
        'release_year' => 2005,
        'vote_average' => 8.5,
    ]);
    $wrongGenre->genres()->attach($action->id);
    attachDirector($wrongGenre, 'Christopher Nolan');

    $wrongYear = Title::factory()->movie()->create([
        'title' => 'Wrong Year Movie',
        'release_year' => 1990,
        'vote_average' => 8.5,
    ]);
    $wrongYear->genres()->attach([$action->id, $comedy->id]);
    attachDirector($wrongYear, 'Christopher Nolan');

    $wrongRating = Title::factory()->movie()->create([
        'title' => 'Wrong Rating Movie',
        'release_year' => 2005,
        'vote_average' => 5.0,
    ]);
    $wrongRating->genres()->attach([$action->id, $comedy->id]);
    attachDirector($wrongRating, 'Christopher Nolan');

    $wrongDirector = Title::factory()->movie()->create([
        'title' => 'Wrong Director Movie',
        'release_year' => 2005,
        'vote_average' => 8.5,
    ]);
    $wrongDirector->genres()->attach([$action->id, $comedy->id]);
    attachDirector($wrongDirector, 'Steven Spielberg');

    $query = http_build_query([
        'type' => 'movie',
        'genres' => [$action->id, $comedy->id],
        'year_from' => 2000,
        'year_to' => 2010,
        'director' => 'nolan',
        'min_rating' => 8,
    ]);

    $response = $this->actingAs($this->user)->get('/browse?'.$query);

    $response->assertOk()
        ->assertSee('Matching Movie')
        ->assertDontSee('Wrong Type Show')
        ->assertDontSee('Wrong Genre Movie')
        ->assertDontSee('Wrong Year Movie')
        ->assertDontSee('Wrong Rating Movie')
        ->assertDontSee('Wrong Director Movie');

    expect($response->viewData('titles')->total())->toBe(1);
});

test('invalid filter values are rejected by validation', function () {
    $response = $this->actingAs($this->user)->get('/browse?'.http_build_query([
        'type' => 'documentary',
        'genres' => [999999],
        'year_from' => 2000,
        'year_to' => 1990,
        'director' => 'ab',
        'min_rating' => 11,
        'sort' => 'alphabetical',
    ]));

    $response->assertRedirect()
        ->assertSessionHasErrors(['type', 'genres.0', 'year_to', 'director', 'min_rating', 'sort']);
});

test('pagination keeps the active filters in the links', function () {
    Title::factory()->count(30)->movie()->create([
        'vote_average' => 8.0,
        'release_year' => 2015,
    ]);

    $query = http_build_query(['type' => 'movie', 'min_rating' => 7, 'sort' => 'rating']);
    $response = $this->actingAs($this->user)->get('/browse?'.$query.'&page=2');

    $response->assertOk()->assertSee('Page 2 of 2');

    $paginator = $response->viewData('titles');

    expect($paginator->count())->toBe(6)
        ->and($paginator->previousPageUrl())->toContain('type=movie')
        ->and($paginator->previousPageUrl())->toContain('min_rating=7')
        ->and($paginator->previousPageUrl())->toContain('sort=rating');
});

test('empty result shows the not-found state', function () {
    $this->actingAs($this->user)
        ->get('/browse?director=NoSuchDirector')
        ->assertOk()
        ->assertSee('We could not find that title yet');
});
