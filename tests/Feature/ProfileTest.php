<?php

use App\Models\Title;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/account');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('account page displays profile and plan details', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/account')
        ->assertOk()
        ->assertSee('Subscription')
        ->assertSee('AI searches left today');
});

test('account deletion removes personal data and invalidates sessions', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $userId = $user->id;
    $watchlist = $user->watchlist()->create(['title_id' => Title::factory()->create()->id]);
    $sessionId = 'other-session';
    DB::table('sessions')->insert([
        'id' => $sessionId,
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->actingAs($user)->delete('/account', ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['id' => $userId]);
    $this->assertDatabaseMissing('watchlists', ['id' => $watchlist->id]);
    $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
});

test('account deletion requires password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/account')->delete('/account')
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/account');

    $this->assertNotNull($user->fresh());
});
