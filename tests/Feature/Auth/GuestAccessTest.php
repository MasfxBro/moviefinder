<?php

use App\Models\User;

test('guests can access public routes', function () {
    $this->get('/')->assertStatus(200);
    $this->get('/pricing')->assertStatus(200);
    $this->get('/terms')->assertStatus(200);
    $this->get('/privacy')->assertStatus(200);
    $this->get('/copyright')->assertStatus(200);
    $this->get('/about')->assertStatus(200);
    $this->get('/version')->assertStatus(200);
    $this->get('/login')->assertStatus(200);
    $this->get('/register')->assertStatus(200);
});

test('legal and attribution pages contain exact sentences', function () {
    $this->get('/about')
        ->assertStatus(200)
        ->assertSee('This product uses the TMDB API but is not endorsed or certified by TMDB.')
        ->assertSee('Where-to-watch data provided by JustWatch.');

    $this->get('/')
        ->assertStatus(200)
        ->assertSee('This product uses the TMDB API but is not endorsed or certified by TMDB.')
        ->assertSee('Where-to-watch data provided by JustWatch.');
});

test('guests cannot access protected routes', function () {
    $response = $this->get('/dashboard');

    // Dashboard requires auth+verified, guests get redirected
    $response->assertRedirect();
});

test('guests are redirected from every protected GET route', function () {
    $paths = [
        '/account',
        '/ai/status',
        '/browse',
        '/dashboard',
        '/feedback',
        '/history',
        '/history/recent',
        '/profile',
        '/requests',
        '/requests/create',
        '/search?q=Matrix',
        '/titles/example',
        '/verify-email',
        '/watchlist',
    ];

    foreach ($paths as $path) {
        $this->get($path)->assertRedirect(route('login'));
    }
});

test('unverified users cannot access verified routes', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect('/verify-email');
});

test('verified users can access dashboard after login', function () {
    $user = User::factory()->create();

    $loginResponse = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $loginResponse->assertRedirect('/dashboard');

    $this->get('/dashboard')->assertStatus(200);
});

test('logged in user opening slash gets home not landing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
    $response->assertViewIs('home');
});
