<?php

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    config(['admin.path' => 'private-ops-test']);
});

test('admin login is unindexed and protected pages share the normal 404', function () {
    $response = $this->get('/private-ops-test/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex');
    expect($response->getContent())->not->toContain('private-ops-test');

    $this->get('/private-ops-test')->assertNotFound();
    $this->actingAs(User::factory()->create())->get('/private-ops-test')->assertNotFound();

    $admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($admin, 'admin')->get('/private-ops-test')->assertNotFound();
});

test('admin login requires second factor and completes it', function () {
    Notification::fake();
    $secret = app(Google2FA::class)->generateSecretKey();
    $admin = User::factory()->create([
        'role' => Role::Admin,
        'admin_totp_secret' => $secret,
        'admin_recovery_codes' => [],
    ]);

    $response = $this->post('/private-ops-test/login', ['email' => $admin->email, 'password' => 'password']);
    $response->assertRedirect(route('admin.2fa'));
    $this->get('/private-ops-test')->assertNotFound();

    $code = app(Google2FA::class)->getCurrentOtp($secret);
    $this->post('/private-ops-test/two-factor', ['code' => $code])->assertRedirect(route('admin.dashboard'));
    $this->get('/private-ops-test')->assertOk()->assertSee('ADMIN');
});

test('audit logger writes hashed client address', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($admin, 'admin')->post('/private-ops-test/login', ['email' => $admin->email, 'password' => 'wrong']);
    $this->withSession(['admin_2fa_verified' => true])->actingAs($admin, 'admin');

    app(AuditLogger::class)->log(request(), 'admin.test');

    expect(AuditLog::where('action', 'admin.test')->exists())->toBeTrue();
    expect(AuditLog::where('action', 'admin.test')->first()->ip_hash)->toBe(hash_hmac('sha256', '127.0.0.1', (string) config('app.key')));
});
