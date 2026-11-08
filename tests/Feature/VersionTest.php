<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

afterEach(function () {
    if (File::exists(base_path('BUILD_ID'))) {
        File::delete(base_path('BUILD_ID'));
    }
});

test('version endpoint returns build id and no-store header', function () {
    config()->set('app.build', 'test-release-2026');

    $response = $this->get('/version');

    $response->assertOk()
        ->assertJson(['build' => 'test-release-2026']);

    $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
});

test('version endpoint reflects BUILD_ID file when present', function () {
    File::put(base_path('BUILD_ID'), "build-from-file-42\n");

    $response = $this->get('/version');

    $response->assertOk()
        ->assertJson(['build' => 'build-from-file-42']);

    $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
});

test('version endpoint is throttled to 30 requests per minute', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->get('/version')->assertOk();
    }

    $response = $this->get('/version');
    $response->assertStatus(429);
});

test('layout renders app-build meta tag and update toast for guests', function () {
    config()->set('app.build', 'build-guest-layout');

    $response = $this->get('/pricing');

    $response->assertOk()
        ->assertSee('<meta name="app-build" content="build-guest-layout">', false)
        ->assertSee('A new version is available');
});

test('layout renders app-build meta tag and update toast for authenticated users', function () {
    config()->set('app.build', 'build-app-layout');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/browse');

    $response->assertOk()
        ->assertSee('<meta name="app-build" content="build-app-layout">', false)
        ->assertSee('A new version is available');
});
