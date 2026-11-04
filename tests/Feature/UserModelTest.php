<?php

use App\Enums\Plan;
use App\Enums\Role;
use App\Models\User;

test('user has default role of user', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(Role::User);
});

test('user can be admin', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    expect($user->isAdmin())->toBeTrue();
});

test('regular user is not admin', function () {
    $user = User::factory()->create(['role' => Role::User]);

    expect($user->isAdmin())->toBeFalse();
});

test('user plan returns free by default', function () {
    $user = User::factory()->create();

    expect($user->plan())->toBe(Plan::Free);
});

test('age confirmed at is nullable', function () {
    $user = User::factory()->create(['age_confirmed_at' => null]);

    expect($user->age_confirmed_at)->toBeNull();
});

test('suspended at is nullable', function () {
    $user = User::factory()->create(['suspended_at' => null]);

    expect($user->suspended_at)->toBeNull();
});
