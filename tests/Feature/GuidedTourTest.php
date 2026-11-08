<?php

declare(strict_types=1);

use App\Enums\OnboardingOutcome;
use App\Enums\Plan;
use App\Enums\Role;
use App\Models\User;
use App\Services\OnboardingService;
use App\Services\Settings;

test('offer appears once after verification and never again on reload', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'onboarding_seen_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/');
    $response->assertOk();
    $response->assertSee('Would you like a short guided tour of the website?');

    $user->refresh();
    expect($user->onboarding_seen_at)->not->toBeNull();

    // On reload, offer must not appear
    $reloadResponse = $this->actingAs($user)->get('/');
    $reloadResponse->assertOk();
    $reloadResponse->assertDontSee('Would you like a short guided tour of the website?');
});

test('offer is not shown again after user skips or completes tour', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'onboarding_seen_at' => null,
    ]);

    // Skip outcome
    $response = $this->actingAs($user)->postJson('/onboarding/outcome', [
        'outcome' => 'skipped',
    ]);
    $response->assertOk();

    $user->refresh();
    expect($user->onboarding_outcome)->toBe(OnboardingOutcome::Skipped);

    $homeResponse = $this->actingAs($user)->get('/');
    $homeResponse->assertDontSee('Would you like a short guided tour of the website?');

    // Complete outcome
    $response = $this->actingAs($user)->postJson('/onboarding/outcome', [
        'outcome' => 'completed',
    ]);
    $response->assertOk();

    $user->refresh();
    expect($user->onboarding_outcome)->toBe(OnboardingOutcome::Completed);
});

test('offer is not offered to unverified users or admins', function (): void {
    // Unverified user
    $unverified = User::factory()->unverified()->create([
        'onboarding_seen_at' => null,
    ]);

    $response = $this->actingAs($unverified)->get('/');
    $response->assertOk();
    $response->assertDontSee('Would you like a short guided tour of the website?');
    expect($unverified->refresh()->onboarding_seen_at)->toBeNull();

    // Admin user
    $admin = User::factory()->create([
        'role' => Role::Admin,
        'email_verified_at' => now(),
        'onboarding_seen_at' => null,
    ]);

    $response = $this->actingAs($admin)->get('/');
    $response->assertOk();
    $response->assertDontSee('Would you like a short guided tour of the website?');
    expect($admin->refresh()->onboarding_seen_at)->toBeNull();

    // Disabled feature flag
    app(Settings::class)->set('onboarding.enabled', false);
    $verified = User::factory()->create([
        'email_verified_at' => now(),
        'onboarding_seen_at' => null,
    ]);

    $response = $this->actingAs($verified)->get('/');
    $response->assertOk();
    $response->assertDontSee('Would you like a short guided tour of the website?');
    expect($verified->refresh()->onboarding_seen_at)->toBeNull();
});

test('each plan variant has its steps', function (): void {
    $service = app(OnboardingService::class);

    $freeSteps = $service->getSteps(Plan::Free);
    expect($freeSteps)->toHaveCount(6);
    expect($freeSteps[0]['target'])->toBe('search');
    expect($freeSteps[1]['target'])->toBe('nav-links');
    expect($freeSteps[2]['target'])->toBe('poster-card');
    expect($freeSteps[3]['target'])->toBe('ai-button');
    expect($freeSteps[3]['content'])->toContain('daily free allowance');
    expect($freeSteps[4]['target'])->toBe('avatar-menu');
    expect($freeSteps[5]['target'])->toBe('navbar');
    expect($freeSteps[5]['title'])->toBe('Subscription plans');

    $plusSteps = $service->getSteps(Plan::Plus);
    expect($plusSteps)->toHaveCount(6);
    expect($plusSteps[3]['content'])->toContain('Plus allowance');
    expect($plusSteps[5]['title'])->toBe('Allowance meter');

    $proSteps = $service->getSteps(Plan::Pro);
    expect($proSteps)->toHaveCount(6);
    expect($proSteps[3]['content'])->toContain('smart searches');
    expect($proSteps[5]['title'])->toBe('Smart searches');
});

test('replay link in account does not change onboarding_seen_at', function (): void {
    $seenDate = now()->subDays(3)->startOfSecond();
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'onboarding_seen_at' => $seenDate,
    ]);

    // Check account page contains the link
    $accountResponse = $this->actingAs($user)->get('/account');
    $accountResponse->assertOk();
    $accountResponse->assertSee('Take the tour again');
    $accountResponse->assertSee(route('home', ['tour' => 1]));

    // Visit home via replay link
    $homeResponse = $this->actingAs($user)->get('/?tour=1');
    $homeResponse->assertOk();
    $homeResponse->assertSee('guided-tour-steps');
    $homeResponse->assertDontSee('Would you like a short guided tour of the website?');

    $user->refresh();
    expect($user->onboarding_seen_at->equalTo($seenDate))->toBeTrue();
});

test('reset works via service and artisan command', function (): void {
    $service = app(OnboardingService::class);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'onboarding_seen_at' => now()->subDay(),
        'onboarding_outcome' => OnboardingOutcome::Completed,
    ]);

    // Reset via service
    $service->reset($user);
    $user->refresh();
    expect($user->onboarding_seen_at)->toBeNull();
    expect($user->onboarding_outcome)->toBeNull();

    // Mark seen again
    $service->markSeen($user);
    $service->recordOutcome($user, OnboardingOutcome::Skipped);
    $user->refresh();
    expect($user->onboarding_seen_at)->not->toBeNull();
    expect($user->onboarding_outcome)->toBe(OnboardingOutcome::Skipped);

    // Reset via artisan command with ID
    $this->artisan('onboarding:reset', ['user' => (string) $user->id])
        ->assertSuccessful();

    $user->refresh();
    expect($user->onboarding_seen_at)->toBeNull();
    expect($user->onboarding_outcome)->toBeNull();

    // Set again and reset via artisan command with email
    $service->markSeen($user);
    $this->artisan('onboarding:reset', ['user' => $user->email])
        ->assertSuccessful();

    $user->refresh();
    expect($user->onboarding_seen_at)->toBeNull();
    expect($user->onboarding_outcome)->toBeNull();

    // Once reset, user can see offer again
    $response = $this->actingAs($user)->get('/');
    $response->assertSee('Would you like a short guided tour of the website?');
});
