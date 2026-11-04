<?php

use App\Models\User;

test('suspended user cannot access protected routes', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});

test('non-suspended user can access protected routes', function () {
    $user = User::factory()->create([
        'suspended_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
});
