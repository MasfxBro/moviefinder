<?php

declare(strict_types=1);

use App\Models\Feedback;
use App\Models\User;
use App\Services\Settings;

test('guests are redirected to login when visiting feedback', function (): void {
    $this->get('/feedback')->assertRedirect(route('login'));
});

test('unverified users are redirected to email verification', function (): void {
    $unverified = User::factory()->unverified()->create();

    $this->actingAs($unverified)
        ->get('/feedback')
        ->assertRedirect(route('verification.notice'));
});

test('verified user can submit feedback with same-origin path', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/feedback', [
        'type' => 'suggestion',
        'message' => 'Please add more vintage Indonesian cinema from the 1980s.',
        'rating' => 5,
        'page_path' => '/browse',
    ]);

    $response->assertRedirect(route('feedback.create'));
    $response->assertSessionHas('status', 'thank-you');

    $this->assertDatabaseHas('feedback', [
        'user_id' => $user->id,
        'type' => 'suggestion',
        'message' => 'Please add more vintage Indonesian cinema from the 1980s.',
        'rating' => 5,
        'page_path' => '/browse',
        'status' => 'new',
    ]);
});

test('rate limit: enforces 3 submissions per day for verified users', function (): void {
    $user = User::factory()->create();

    for ($i = 1; $i <= 3; $i++) {
        $this->actingAs($user)->post('/feedback', [
            'type' => 'bug',
            'message' => "This is bug report submission number {$i}.",
        ])->assertRedirect(route('feedback.create'));
    }

    expect(Feedback::where('user_id', $user->id)->count())->toBe(3);

    // 4th submission hits rate limit -> 429
    $response = $this->actingAs($user)->post('/feedback', [
        'type' => 'bug',
        'message' => 'This is the 4th feedback submission which should fail.',
    ]);

    $response->assertStatus(429);
});

test('honeypot: rejects submission when honeypot field is filled', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/feedback', [
        'type' => 'suggestion',
        'message' => 'This message was submitted by an automated bot.',
        'honeypot' => 'bot-filled-honeypot',
    ]);

    $response->assertSessionHasErrors(['honeypot']);
    expect(Feedback::where('message', 'like', '%automated bot%')->exists())->toBeFalse();

    $responseWebsite = $this->actingAs($user)->post('/feedback', [
        'type' => 'suggestion',
        'message' => 'Another bot submission via website field.',
        'website' => 'https://spam.example.com',
    ]);

    $responseWebsite->assertSessionHasErrors(['honeypot']);
    expect(Feedback::where('message', 'like', '%Another bot submission%')->exists())->toBeFalse();
});

test('rejects cross-origin page path in feedback form', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/feedback', [
        'type' => 'bug',
        'message' => 'Valid feedback message testing page path boundary.',
        'page_path' => 'https://external-phishing-site.example/steal',
    ]);

    $response->assertSessionHasErrors(['page_path']);
});

test('plain text escaped in thank-you display', function (): void {
    $user = User::factory()->create();
    $xssMessage = 'Testing <script>alert("xss")</script> and <b>markup</b> tags.';

    $response = $this->actingAs($user)->post('/feedback', [
        'type' => 'other',
        'message' => $xssMessage,
    ]);

    $response->assertRedirect(route('feedback.create'));

    $screenResponse = $this->actingAs($user)->get('/feedback');
    $screenResponse->assertOk()
        ->assertSee('Thank you for your feedback!')
        ->assertDontSee('<script>alert("xss")</script>', false)
        ->assertSee('Testing alert("xss") and markup tags.');
});

test('flag off shows the friendly message for feedback', function (): void {
    $user = User::factory()->create();
    app(Settings::class)->set('features.feedback_open', false);

    $response = $this->actingAs($user)->get('/feedback');

    $response->assertOk()
        ->assertSee('Feedback is under maintenance')
        ->assertDontSee('name="message"', false);
});
