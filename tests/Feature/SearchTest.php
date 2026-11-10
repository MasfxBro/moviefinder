<?php

declare(strict_types=1);

use App\Models\Title;
use App\Models\User;
use App\Services\Tmdb\TmdbClient;
use App\Services\Tmdb\TmdbMapper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

beforeEach(function () {
    Config::set('services.tmdb.token', 'test-token');
    $this->app->singleton(TmdbClient::class, function () {
        return new TmdbClient('test-token', new TmdbMapper);
    });
});

test('guest is redirected to login from search page', function () {
    $this->get('/search?q=Matrix')->assertRedirect(route('login'));
});

test('unverified users are redirected from the search page', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/search?q=Matrix')
        ->assertRedirect('/verify-email');
});

test('search endpoint returns results', function () {
    $user = User::factory()->create();

    Title::factory()->create(['title' => 'The Matrix', 'media_type' => 'movie', 'release_year' => 1999]);

    $response = $this->actingAs($user)->getJson('/search?q=Matrix');

    $response->assertOk()
        ->assertJsonFragment(['title' => 'The Matrix'])
        ->assertJsonFragment(['release_year' => 1999])
        ->assertJsonFragment(['slug' => 'the-matrix-1999']);
});

test('search results render title markup as text', function () {
    $user = User::factory()->create();
    $title = '<img src=x onerror=alert(1)>';
    Title::factory()->create(['title' => $title]);

    $response = $this->actingAs($user)->get('/search?q=img');

    $response->assertOk()
        ->assertSee($title)
        ->assertDontSee($title, false);
});

test('search page renders not-found state with request button', function () {
    $user = User::factory()->create();

    Http::fake([
        '*' => Http::response(['results' => []], 200),
    ]);

    $response = $this->actingAs($user)->get('/search?q=NonexistentTitle12345');

    $response->assertOk()
        ->assertViewIs('search')
        ->assertSee('We could not find that title yet')
        ->assertSee('Request this title');
});

test('search endpoint returns JSON suggestions for prefix and substring matches', function () {
    $user = User::factory()->create();
    Title::factory()->create(['title' => 'Spider-Man', 'popularity' => 10]);
    Title::factory()->create(['title' => 'A Special Story', 'popularity' => 50]);

    $response = $this->actingAs($user)->getJson('/search?q=sp');

    $response->assertOk()
        ->assertJsonPath('data.0.title', 'A Special Story')
        ->assertJsonFragment(['title' => 'Spider-Man']);
});

test('single-character search returns matching catalog titles', function () {
    $user = User::factory()->create();
    Title::factory()->create(['title' => 'Spider-Man', 'original_title' => null, 'popularity' => 50]);
    Title::factory()->create(['title' => 'Batman', 'original_title' => null, 'popularity' => 10]);

    $response = $this->actingAs($user)->getJson('/search?q=s');

    $response->assertOk()
        ->assertJsonPath('data.0.title', 'Spider-Man')
        ->assertJsonMissing(['title' => 'Batman']);
});
