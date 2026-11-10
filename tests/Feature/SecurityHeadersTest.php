<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Vite;

it('adds report-only security headers with a Vite nonce locally', function (): void {
    $this->app['env'] = 'local';

    $response = $this->get('/version');
    $csp = (string) $response->headers->get('Content-Security-Policy-Report-Only');
    $nonce = Vite::cspNonce();

    $response
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeaderMissing('Strict-Transport-Security')
        ->assertHeaderMissing('Content-Security-Policy');

    expect($nonce)->toBeString()
        ->and($csp)->toContain("script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://www.youtube.com")
        ->and($csp)->toContain("img-src 'self' data: https://image.tmdb.org https://i.ytimg.com")
        ->and($csp)->toContain('frame-src https://www.youtube-nocookie.com https://www.youtube.com')
        ->and($csp)->toContain("frame-ancestors 'none'");
});

it('enforces security headers and HSTS in production', function (): void {
    $this->app['env'] = 'production';

    $response = $this->get('/version');

    $response
        ->assertOk()
        ->assertHeader('Content-Security-Policy')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
        ->assertHeaderMissing('Content-Security-Policy-Report-Only');
});
