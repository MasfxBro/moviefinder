<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('admin accounts cannot login through public login', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $response = $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('suspended users receive the generic invalid credentials error', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
    expect($response->exception->errors()['email'][0])->toBe(trans('auth.failed'));
});

test('login failures trigger progressive delay', function () {
    $user = User::factory()->create();

    foreach (range(1, 3) as $attempt) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    expect($response->exception->errors()['email'][0])->toContain('3 seconds');
    expect(RateLimiter::attempts('login-delay:'.strtolower($user->email).'|127.0.0.1'))->toBeGreaterThan(0);
});

test('login is throttled', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    expect($response->exception->getMessage())->toContain('Too many');
});
