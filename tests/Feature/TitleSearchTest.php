<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TitleSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function local_hit_returns_results(): void
    {
        Title::factory()->create(['title' => 'Matrix', 'adult' => false]);
        $response = $this->getJson('/search?q=Matrix');
        $response->assertOk();
        $response->assertJsonPath('data.0.title', 'Matrix')
            ->assertJsonPath('data.0.slug', 'matrix');
    }

    /** @test */
    public function substring_search_returns_popular_titles_across_title_words(): void
    {
        Title::factory()->create(['title' => 'Spider-Man', 'popularity' => 10, 'adult' => false]);
        Title::factory()->create(['title' => 'A Special Story', 'popularity' => 50, 'adult' => false]);
        Title::factory()->create(['title' => 'Hidden Spider', 'is_hidden' => true, 'adult' => false]);
        Title::factory()->create(['title' => 'Adult Spider', 'adult' => true]);

        $response = $this->getJson('/search?q=sp');

        $response->assertOk()
            ->assertJsonPath('data.data.0.title', 'A Special Story')
            ->assertJsonFragment(['title' => 'Spider-Man'])
            ->assertJsonMissing(['title' => 'Hidden Spider'])
            ->assertJsonMissing(['title' => 'Adult Spider']);
    }

    /** @test */
    public function escaped_like_works(): void
    {
        Title::factory()->create(['title' => '100% Real', 'adult' => false]);
        $response = $this->getJson('/search?q=100%');
        $response->assertOk();
        $response->assertJsonCount(1);
        $this->assertEquals('100% Real', $response->json()[0]['title']);
    }

    /** @test */
    public function adult_titles_are_excluded(): void
    {
        Title::factory()->create(['title' => 'Adult Movie', 'adult' => true]);
        $response = $this->getJson('/search?q=Adult');
        $response->assertOk();
        $response->assertJsonCount(0);
    }

    /** @test */
    public function tmdb_fallback_returns_not_in_catalog_flag(): void
    {
        Http::fake([
            '*' => Http::response(['results' => [['id' => 123, 'title' => 'Fake TMDB', 'adult' => false]]], 200),
        ]);
        $response = $this->getJson('/search?q=Nonexistent');
        $response->assertOk();
        $response->assertJsonFragment(['not_in_catalog' => true]);
    }

    /** @test */
    public function miss_counter_increments_when_no_results(): void
    {
        Http::fake(['*' => Http::response(['results' => []], 200)]);
        $this->assertDatabaseMissing('search_misses', ['query_norm' => 'nothing']);
        $this->getJson('/search?q=nothing');
        $this->assertDatabaseHas('search_misses', ['query_norm' => 'nothing', 'hits' => 1]);
    }

    /** @test */
    public function route_has_rate_limit_middleware(): void
    {
        $route = Route::getRoutes()->getByName('search');
        $this->assertNotNull($route);
        $this->assertStringContainsString('throttle:60,1', implode('|', $route->gatherMiddleware()));
    }
}
