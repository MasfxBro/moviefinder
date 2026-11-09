<?php

use App\Enums\Role;
use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['admin.path' => 'private-ops-test']);
    // create admin user
    $this->admin = User::factory()->create(['role' => Role::Admin]);
    // create regular user
    $this->user = User::factory()->create(['role' => Role::User]);

    $this->withSession(['admin_2fa_verified' => true])
        ->actingAs($this->admin, 'admin');
});

test('admin cannot view another user watchlist', function () {
    $response = getJson('/private-ops-test/users/'.$this->user->id.'/watchlist');
    $response->assertStatus(403);
});

test('admin cannot view another user history', function () {
    $response = getJson('/private-ops-test/users/'.$this->user->id.'/history');
    $response->assertStatus(403);
});

test('manual plan grant requires reason and expiry', function () {
    // missing reason and expires_at
    $response = postJson('/private-ops-test/users/'.$this->user->id.'/grant-plan', [
        'plan' => 'plus',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['reason', 'expires_at']);
});
