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

test('search page renders results', function () {
    $user = User::factory()->create();

    Title::factory()->create(['title' => 'The Matrix', 'media_type' => 'movie', 'release_year' => 1999]);

    $response = $this->actingAs($user)->get('/search?q=Matrix');

    $response->assertOk()
        ->assertViewIs('search')
        ->assertSee('The Matrix')
        ->assertSee('1999');
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

test('search page requires at least 2 characters', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/search?q=M');

    $response->assertOk()
        ->assertViewIs('search')
        ->assertDontSee('The Matrix');
});
