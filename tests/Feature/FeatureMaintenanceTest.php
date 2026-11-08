<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Settings;
use Illuminate\Support\Facades\Blade;

test('flag off shows friendly message when AI is under maintenance', function (): void {
    $user = User::factory()->create();
    app(Settings::class)->set('features.ai_enabled', false);

    // Endpoint returns 200 with friendly message, never an error
    $response = $this->actingAs($user)->postJson('/ai/messages', [
        'message' => 'Find me a good action movie.',
        'mode' => 'global',
    ]);

    $response->assertOk()
        ->assertJsonPath('reply', 'AI search is under maintenance. Normal search still works. Please try again later.');

    // Component renders friendly replacement
    $html = Blade::render('<x-ai-chat />');
    expect($html)->toContain('AI search is under maintenance')
        ->and($html)->not->toContain('name="message"');
});

test('flag off shows friendly message when requests are under maintenance', function (): void {
    $user = User::factory()->create();
    app(Settings::class)->set('features.requests_open', false);

    $response = $this->actingAs($user)->get('/requests/create');

    $response->assertOk()
        ->assertSee('Title requests are under maintenance')
        ->assertDontSee('Submit request');
});

test('flag off shows friendly message when feedback is under maintenance', function (): void {
    $user = User::factory()->create();
    app(Settings::class)->set('features.feedback_open', false);

    $response = $this->actingAs($user)->get('/feedback');

    $response->assertOk()
        ->assertSee('Feedback is under maintenance')
        ->assertDontSee('Send feedback');
});

test('flag off shows friendly message when registration is under maintenance', function (): void {
    app(Settings::class)->set('features.registration_open', false);

    $response = $this->get('/register');

    $response->assertOk()
        ->assertSee('Registration is under maintenance')
        ->assertDontSee('name="password"', false);
});

test('reusable available at public launch notice component renders friendly notice (D-49)', function (): void {
    // Card variant
    $cardHtml = Blade::render('<x-public-launch-notice feature="Real charging" />');
    expect($cardHtml)->toContain('Real charging')
        ->and($cardHtml)->toContain('available at public launch');

    // Label / badge variant
    $badgeHtml = Blade::render('<x-public-launch-notice feature="OVO auto-debit" variant="badge" />');
    expect($badgeHtml)->toContain('OVO auto-debit will be available at public launch.')
        ->and($badgeHtml)->toContain('bg-accent/15');
});
