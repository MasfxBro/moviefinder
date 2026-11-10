<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration setting blocks account creation', function () {
    config(['features.registration_open' => false]);

    $this->post('/register', [
        'name' => 'Blocked User',
        'email' => 'blocked@example.com',
        'password' => 'UniquePass2026!@#',
        'password_confirmation' => 'UniquePass2026!@#',
        'age_confirmed' => '1',
        'terms_accepted' => '1',
    ])->assertNotFound();

    expect(User::where('email', 'blocked@example.com')->exists())->toBeFalse();
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'UniquePass2026!@#',
        'password_confirmation' => 'UniquePass2026!@#',
        'age_confirmed' => '1',
        'terms_accepted' => '1',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->age_confirmed_at)->not->toBeNull();
});

test('registration ignores a hostile role', function () {
    $this->post('/register', [
        'name' => 'Regular User',
        'email' => 'regular@example.com',
        'password' => 'UniquePass2026!@#',
        'password_confirmation' => 'UniquePass2026!@#',
        'age_confirmed' => '1',
        'terms_accepted' => '1',
        'role' => 'admin',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(User::where('email', 'regular@example.com')->sole()->role->value)->toBe('user');
});

test('registration requires age confirmation', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'terms_accepted' => '1',
    ]);

    $response->assertSessionHasErrors('age_confirmed');
    $this->assertGuest();
});

test('registration requires terms acceptance', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'age_confirmed' => '1',
    ]);

    $response->assertSessionHasErrors('terms_accepted');
    $this->assertGuest();
});

test('password must be at least 10 characters', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Short1!',
        'password_confirmation' => 'Short1!',
        'age_confirmed' => '1',
        'terms_accepted' => '1',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('registration is throttled', function () {
    for ($i = 0; $i < 6; $i++) {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => "test{$i}@example.com",
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'age_confirmed' => '1',
            'terms_accepted' => '1',
        ]);
    }

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'final@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'age_confirmed' => '1',
        'terms_accepted' => '1',
    ]);

    $response->assertStatus(429);
});
