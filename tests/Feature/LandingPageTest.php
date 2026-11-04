<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('guest sees the landing page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Describe a scene.');
    $response->assertSee('We find the film.');
    $response->assertSee('How it works');
    $response->assertSee('Choose your plan');
});

test('logged in user sees the home page instead of landing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
    $response->assertSee('Your catalog is empty');
    $response->assertDontSee('How it works');
    $response->assertDontSee('Choose your plan');
});

test('poster strip is hidden when catalog is empty', function () {
    // Ensure titles table exists but is empty
    if (Schema::hasTable('titles')) {
        DB::table('titles')->truncate();
    }

    $response = $this->get('/');

    $response->assertDontSee('https://image.tmdb.org/t/p/w342');
});

test('no catalog links in guest html besides the strip', function () {
    $response = $this->get('/');

    // Guest should only have auth links and legal links
    // No links to title detail pages (e.g. /titles/...)
    $response->assertDontSee('/titles/');
});
