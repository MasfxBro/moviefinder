<?php

use App\Models\User;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
    $response->assertSee($user->email, false);
});

test('email can be verified in same browser', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('verification link opened in different browser requires login first', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    // Not authenticated - different browser
    $response = $this->get($verificationUrl);

    $response->assertRedirect(route('login'));
    expect(session('url.intended'))->toBe($verificationUrl);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

    // Now login and the intended URL should verify
    $loginResponse = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $loginResponse->assertRedirect($verificationUrl);

    // Follow redirect to verification URL
    $verifyResponse = $this->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('expired verification link shows clear message', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->subMinutes(1), // Expired
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertRedirect(route('verification.notice'));

    $noticeResponse = $this->actingAs($user)->get(route('verification.notice'));
    $noticeResponse->assertSee('Resend verification email');
});

test('invalid hash shows 403', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertStatus(403);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email verification notification resend is throttled per user', function () {
    $user = User::factory()->unverified()->create();

    // First 3 should succeed
    for ($i = 0; $i < 3; $i++) {
        $response = $this->actingAs($user)->post('/email/verification-notification');
        $response->assertStatus(302);
    }

    // 4th should be throttled
    $response = $this->actingAs($user)->post('/email/verification-notification');
    $response->assertStatus(429);
});

test('changing email address requires re-verification', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    expect($user->hasVerifiedEmail())->toBeTrue();

    Notification::fake();

    // Change email
    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => 'newemail@example.com',
    ]);

    $user->refresh();

    expect($user->email)->toBe('newemail@example.com');
    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('prune command removes only old unverified accounts', function () {
    // Old unverified (should be deleted)
    $oldUnverified = User::factory()->unverified()->create([
        'created_at' => now()->subDays(15),
    ]);

    // Recent unverified (should be kept)
    $recentUnverified = User::factory()->unverified()->create([
        'created_at' => now()->subDays(10),
    ]);

    // Old verified (should be kept)
    $oldVerified = User::factory()->create([
        'created_at' => now()->subDays(20),
        'email_verified_at' => now()->subDays(20),
    ]);

    $this->artisan('users:prune-unverified')->assertSuccessful();

    expect(User::find($oldUnverified->id))->toBeNull();
    expect(User::find($recentUnverified->id))->not->toBeNull();
    expect(User::find($oldVerified->id))->not->toBeNull();
});

test('verification email uses custom notification', function () {
    $user = User::factory()->unverified()->create();

    Notification::fake();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('reset password email uses custom notification', function () {
    $user = User::factory()->create();

    Notification::fake();

    $user->sendPasswordResetNotification('test-token');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('verification email subject and content matches spec', function () {
    $user = User::factory()->unverified()->create(['name' => 'John Doe']);

    Notification::fake();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user) {
        $mailMessage = $notification->toMail($user);

        expect($mailMessage->subject)->toBe('Verify your email address');
        expect($mailMessage->actionText)->toBe('Verify email address');

        $allLines = array_merge($mailMessage->introLines, $mailMessage->outroLines);
        $content = implode(' ', $allLines);

        expect($content)->toContain('Thank you for creating an account');
        expect($content)->toContain('60 minutes');
        expect($content)->toContain('did not create an account');

        return true;
    });
});

test('reset password email subject and content matches spec', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    Notification::fake();

    $user->sendPasswordResetNotification('test-token');

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $mailMessage = $notification->toMail($user);

        expect($mailMessage->subject)->toBe('Reset your password');
        expect($mailMessage->actionText)->toBe('Reset password');

        $allLines = array_merge($mailMessage->introLines, $mailMessage->outroLines);
        $content = implode(' ', $allLines);

        expect($content)->toContain('password reset request');
        expect($content)->toContain('60 minutes');
        expect($content)->toContain('did not request a password reset');

        return true;
    });
});
