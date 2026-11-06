<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'NewSecure123!',
            'password_confirmation' => 'NewSecure123!',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('NewSecure123!', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewSecure123!',
            'password_confirmation' => 'NewSecure123!',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});

test('password change invalidates other idle database sessions', function () {
    config(['session.driver' => 'database']);

    $user = User::factory()->create();
    $otherSessionId = 'other-session-id';

    DB::table('sessions')->insert([
        'id' => $otherSessionId,
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test-agent',
        'payload' => base64_encode(serialize([
            'login_web_'.sha1(User::class) => $user->getAuthIdentifier(),
        ])),
        'last_activity' => now()->timestamp,
    ]);

    $response = $this
        ->actingAs($user)
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'NewSecure123!',
            'password_confirmation' => 'NewSecure123!',
        ]);

    $response->assertRedirect('/login');
    expect(DB::table('sessions')->where('user_id', $user->getKey())->exists())->toBeFalse();
});
