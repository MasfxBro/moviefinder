<?php

declare(strict_types=1);

use App\Models\Genre;
use App\Models\Title;
use App\Models\User;
use Database\Seeders\GenreSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('guest sees welcome landing page at root URL', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertViewIs('welcome');
});

test('cached collections unserialize without incomplete object errors', function () {
    $store = Cache::store('file');
    $store->put('test:collection', collect([['title' => 'Cached']]), 60);

    expect($store->get('test:collection'))->toBeInstanceOf(Collection::class);

    $store->forget('test:collection');
});

test('logged in user renders home page with empty catalog state', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertOk()
        ->assertViewIs('home')
        ->assertSee('Your catalog is empty');
});

test('logged in user renders home page with catalog rows and genre names', function () {
    $this->seed(GenreSeeder::class);

    $user = User::factory()->create();
    $actionGenre = Genre::find(28);

    $movie = Title::factory()->movie()->create([
        'title' => 'Test Action Movie',
        'popularity' => 100,
        'vote_average' => 8.5,
        'vote_count' => 1000,
    ]);
    $movie->genres()->attach($actionGenre->id);

    $tvShow = Title::factory()->tv()->create([
        'title' => 'Test TV Show',
        'popularity' => 90,
        'vote_average' => 8.0,
        'vote_count' => 500,
    ]);
    $tvShow->genres()->attach($actionGenre->id);

    $response = $this->actingAs($user)->get('/');

    $response->assertOk()
        ->assertViewIs('home')
        ->assertDontSee('Your catalog is empty')
        ->assertSee('Popular')
        ->assertSee('Top Rated')
        ->assertSee('Movies')
        ->assertSee('TV Shows')
        ->assertSee('Test Action Movie')
        ->assertSee('Test TV Show')
        ->assertSee('Action');
});

test('home page does not suffer from N+1 query problem', function () {
    $this->seed(GenreSeeder::class);
    $user = User::factory()->create();
    $actionGenre = Genre::find(28);

    // Create initial set of titles
    $titles = Title::factory()->count(5)->movie()->create();
    foreach ($titles as $title) {
        $title->genres()->attach($actionGenre->id);
    }

    // Warm cache and clear query log
    $this->actingAs($user)->get('/');
    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)->get('/');
    $queriesSmallCatalog = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Create 20 more titles
    $moreTitles = Title::factory()->count(20)->movie()->create();
    foreach ($moreTitles as $title) {
        $title->genres()->attach($actionGenre->id);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($user)->get('/');
    $queriesLargeCatalog = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesLargeCatalog)->toBe($queriesSmallCatalog);
});
