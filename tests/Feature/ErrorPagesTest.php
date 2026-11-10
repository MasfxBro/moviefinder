<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Config::set('app.debug', false);
    Config::set('admin.path', 'ops-secret-path-12345');
});

test('404 error page returns 404 status and exact copy', function () {
    $response = $this->get('/non-existent-page-url');

    $response->assertStatus(404);
    $response->assertSee('Page not found');
    $response->assertSee('We could not find that page. The link may be incorrect or the page may have moved.');
    $response->assertSee('Go home');
    $response->assertSee('Search titles');
});

test('403 error page returns 403 status and exact copy', function () {
    Route::get('/test-403', fn () => abort(403));

    $response = $this->get('/test-403');

    $response->assertStatus(403);
    $response->assertSee('No access');
    $response->assertSee('You do not have permission to open this page.');
    $response->assertSee('Go home');
});

test('419 error page returns 419 status and exact copy', function () {
    Route::get('/test-419', fn () => abort(419));

    $response = $this->get('/test-419');

    $response->assertStatus(419);
    $response->assertSee('Session expired');
    $response->assertSee('Your session expired. Reload the page and try again.');
    $response->assertSee('Reload');
});

test('429 error page returns 429 status and exact copy', function () {
    Route::get('/test-429', fn () => abort(429));

    $response = $this->get('/test-429');

    $response->assertStatus(429);
    $response->assertSee('Slow down a little');
    $response->assertSee('Too many requests.');
    $response->assertSee('Try again');
});

test('500 error page returns 500 status and reference id', function () {
    Route::get('/test-500', function () {
        throw new Exception('Uncaught test exception');
    });

    $response = $this->get('/test-500');

    $response->assertStatus(500);
    $response->assertSee('Something went wrong');
    $response->assertSee('An error occurred on our side. Please try again in a moment. Reference:');
    $response->assertSee('Try again');
    $response->assertSee('Go home');
});

test('503 error page returns 503 status and exact copy', function () {
    Route::get('/test-503', fn () => abort(503));

    $response = $this->get('/test-503');

    $response->assertStatus(503);
    $response->assertSee('We will be back shortly');
    $response->assertSee('We are performing maintenance.');
    $response->assertSee('Refresh');
});

test('error pages contain no stack trace', function () {
    Route::get('/test-500-trace', function () {
        throw new RuntimeException('Fatal internal error');
    });

    $response = $this->get('/test-500-trace');

    $response->assertStatus(500);
    $response->assertDontSee('Fatal internal error');
    $response->assertDontSee('Stack trace:');
    $response->assertDontSee('vendor/laravel/framework');
});

test('error pages contain no internal names or the admin path', function () {
    $adminPath = config('admin.path');

    Route::get('/test-500-internal', fn () => throw new RuntimeException('InternalServiceName failure'));

    foreach (['/unknown-url', '/'.$adminPath.'/unknown', '/test-500-internal'] as $url) {
        $response = $this->get($url);

        $response->assertDontSee('ADMIN_PATH')
            ->assertDontSee($adminPath)
            ->assertDontSee('InternalServiceName');
    }
});

test('request failed component renders retry button and message', function () {
    $view = $this->blade('<x-request-failed retry="fetchData()" />');

    $view->assertSee('The server could not be reached. Please check your connection and try again.');
    $view->assertSee('Retry');
    $view->assertSee('fetchData()');
});
