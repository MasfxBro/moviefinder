<?php

declare(strict_types=1);

use App\Contracts\PlanResolver;
use App\Enums\Plan;
use App\Enums\Role;
use App\Models\SearchHistory;
use App\Models\Title;
use App\Models\User;
use App\Services\Ai\ModelRouter;
use Illuminate\Support\Facades\Vite;

// ---------------------------------------------------------------------------
// IDOR
// ---------------------------------------------------------------------------

it('prevents IDOR on watchlist: user B cannot remove user A title', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $title = Title::factory()->movie()->create();

    // Add title to user A's watchlist
    $this->actingAs($userA)->post(route('watchlist.toggle', $title))->assertOk();

    // User B toggling the same title only affects B's own watchlist
    $this->actingAs($userB)->post(route('watchlist.toggle', $title))->assertOk();

    // User A's watchlist entry is preserved
    $this->assertDatabaseHas('watchlists', ['user_id' => $userA->id, 'title_id' => $title->id]);
});

it('prevents IDOR on history: user B clearing history does not remove user A entries', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SearchHistory::create(['user_id' => $userA->id, 'type' => 'normal', 'query' => 'secret search']);

    $this->actingAs($userB)->delete(route('history.destroy'))->assertRedirect();

    // User A's history entry must still exist
    $this->assertDatabaseHas('search_history', ['user_id' => $userA->id, 'query' => 'secret search']);
});

// ---------------------------------------------------------------------------
// Mass assignment
// ---------------------------------------------------------------------------

it('ignores role and suspended_at in profile update payload', function (): void {
    $user = User::factory()->create(['role' => Role::User]);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Alice',
        'email' => $user->email,
        'role' => 'admin',
        'suspended_at' => now()->toDateTimeString(),
    ]);

    expect($user->fresh()->role)->toBe(Role::User)
        ->and($user->fresh()->suspended_at)->toBeNull();
});

it('ignores mass-assignment of role and suspended_at in name update', function (): void {
    $user = User::factory()->create(['role' => Role::User]);

    $this->actingAs($user)->patch(route('account.name.update'), [
        'name' => 'Alice',
        'role' => 'admin',
        'suspended_at' => now()->toDateTimeString(),
    ]);

    expect($user->fresh()->role)->toBe(Role::User)
        ->and($user->fresh()->suspended_at)->toBeNull();
});

// ---------------------------------------------------------------------------
// XSS — name and free-text fields rendered in Blade templates
// ---------------------------------------------------------------------------

it('escapes dangerous content in name when rendered on account page', function (): void {
    $xssName = '<script>alert("xss")</script>';
    $user = User::factory()->create(['name' => $xssName]);

    $response = $this->actingAs($user)->get(route('account.show'));

    $response->assertOk()
        ->assertDontSee('<script>alert("xss")</script>', false)
        ->assertSee('alert("xss")');
});

it('escapes dangerous content in name on profile page', function (): void {
    $user = User::factory()->create(['name' => '<img src=x onerror=alert(1)>']);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk()
        ->assertDontSee('<img src=x onerror=alert(1)>', false);
});

// ---------------------------------------------------------------------------
// CSP nonce — header carries the nonce used by Vite
// ---------------------------------------------------------------------------

it('CSP header carries the per-request script nonce', function (): void {
    $response = $this->get('/login');
    $nonce = Vite::cspNonce();

    expect($nonce)->toBeString()->not->toBeEmpty();

    // In testing/local env the header is report-only
    $csp = (string) ($response->headers->get('Content-Security-Policy-Report-Only')
        ?? $response->headers->get('Content-Security-Policy')
        ?? '');

    expect($csp)->toContain("'nonce-{$nonce}'");
});

it('generates a fresh nonce per request', function (): void {
    $this->get('/login');
    $n1 = Vite::cspNonce();

    $this->get('/login');
    $n2 = Vite::cspNonce();

    expect($n1)->not->toBe($n2);
});

// ---------------------------------------------------------------------------
// Security headers — present on every response
// ---------------------------------------------------------------------------

it('sets all required security headers on web responses', function (string $path): void {
    $this->get($path)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
        ->assertHeader('X-Frame-Options', 'DENY');
})->with(['/', '/login', '/version']);

it('suppresses HSTS outside production', function (): void {
    $this->app['env'] = 'local';
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

    $this->app['env'] = 'testing';
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
});

it('uses report-only CSP in local environment', function (): void {
    $this->app['env'] = 'local';

    $this->get('/')
        ->assertHeader('Content-Security-Policy-Report-Only')
        ->assertHeaderMissing('Content-Security-Policy');
});

// ---------------------------------------------------------------------------
// guest access — every non-public route redirects to login
// ---------------------------------------------------------------------------

it('redirects guests from protected routes to login', function (string $path): void {
    $this->get($path)->assertRedirect(route('login'));
})->with([
    '/dashboard',
    '/browse',
    '/watchlist',
    '/history',
    '/requests',
    '/requests/create',
    '/search?q=test',
    '/ai/status',
    '/account',
    '/profile',
    '/feedback',
]);

// ---------------------------------------------------------------------------
// landing page — no title detail links in guest HTML
// ---------------------------------------------------------------------------

it('serves no title detail links to guests on the landing page', function (): void {
    Title::factory()->movie()->count(3)->create();

    $this->get('/')->assertOk()->assertDontSee('href="/titles/', false);
});

// ---------------------------------------------------------------------------
// Free user key routing
// ---------------------------------------------------------------------------

it('free plan key is never the paid key', function (): void {
    config([
        'services.gemini.free_key' => 'free-key-abc',
        'services.gemini.paid_key' => 'paid-key-xyz',
    ]);

    $user = User::factory()->create();

    $resolver = Mockery::mock(PlanResolver::class);
    $resolver->shouldReceive('resolve')->andReturn(Plan::Free);
    app()->instance(PlanResolver::class, $resolver);

    $route = app(ModelRouter::class)->route($user, true);

    expect($route['key'])->toBe('free-key-abc')
        ->and($route['key'])->not->toBe('paid-key-xyz');
});

// ---------------------------------------------------------------------------
// SQL injection probes on search
// ---------------------------------------------------------------------------

it('handles SQL injection probes in the search query without error', function (string $probe): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/search?q='.urlencode($probe))
        ->assertOk();
})->with([
    "' OR '1'='1",
    '1; DROP TABLE titles--',
    '%" OR "%"="%',
    'UNION SELECT null,null,null--',
]);
