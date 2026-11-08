<?php

declare(strict_types=1);

use App\Models\Announcement;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::forget('announcements.active');
});

test('banner window: active announcement within time window is displayed', function (): void {
    $announcement = Announcement::factory()->create([
        'kind' => 'info',
        'message' => 'Scheduled maintenance tonight at 02:00 WIB.',
        'starts_at' => now()->subMinutes(10),
        'ends_at' => now()->addMinutes(30),
        'is_active' => true,
    ]);

    $response = $this->get('/');
    $response->assertOk()
        ->assertSee('Scheduled maintenance tonight at 02:00 WIB.');
});

test('banner window: upcoming announcement is not displayed', function (): void {
    Announcement::factory()->create([
        'message' => 'Upcoming maintenance banner text.',
        'starts_at' => now()->addHour(),
        'ends_at' => now()->addHours(2),
        'is_active' => true,
    ]);

    $response = $this->get('/');
    $response->assertOk()
        ->assertDontSee('Upcoming maintenance banner text.');
});

test('banner window: expired announcement is not displayed', function (): void {
    Announcement::factory()->create([
        'message' => 'Old expired maintenance announcement.',
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subHour(),
        'is_active' => true,
    ]);

    $response = $this->get('/');
    $response->assertOk()
        ->assertDontSee('Old expired maintenance announcement.');
});

test('banner window: inactive announcement is not displayed even if in window', function (): void {
    Announcement::factory()->create([
        'message' => 'Disabled inactive announcement.',
        'starts_at' => now()->subMinutes(10),
        'ends_at' => now()->addMinutes(30),
        'is_active' => false,
    ]);

    $response = $this->get('/');
    $response->assertOk()
        ->assertDontSee('Disabled inactive announcement.');
});

test('announcements are cached for 60 seconds and invalidated on change', function (): void {
    $announcement = Announcement::factory()->create([
        'message' => 'Cached message v1.',
        'starts_at' => now()->subMinutes(5),
        'ends_at' => now()->addMinutes(15),
        'is_active' => true,
    ]);

    // First call caches the active announcements
    $items = Announcement::currentActive();
    expect($items)->toHaveCount(1)
        ->and(Cache::has('announcements.active'))->toBeTrue();

    // Mutating the model invalidates cache
    $announcement->update(['message' => 'Cached message v2 updated.']);
    expect(Cache::has('announcements.active'))->toBeFalse();

    $refreshed = Announcement::currentActive();
    expect($refreshed->first()->message)->toBe('Cached message v2 updated.');
});

test('plain text escaped in announcement banner output', function (): void {
    $xss = '<script>alert("hack")</script><b>Warning text</b>';

    Announcement::factory()->create([
        'kind' => 'warning',
        'message' => $xss,
        'starts_at' => now()->subMinutes(5),
        'ends_at' => now()->addMinutes(15),
        'is_active' => true,
    ]);

    $response = $this->get('/');
    $response->assertOk()
        ->assertDontSee('<script>alert("hack")</script>', false)
        ->assertSee('alert("hack")')
        ->assertSee('Warning text');
});

test('renders distinct styling for info, warning, and maintenance', function (): void {
    Announcement::factory()->create([
        'kind' => 'info',
        'message' => 'Information banner item.',
        'is_active' => true,
    ]);
    Announcement::factory()->create([
        'kind' => 'warning',
        'message' => 'Warning banner item.',
        'is_active' => true,
    ]);
    Announcement::factory()->create([
        'kind' => 'maintenance',
        'message' => 'Maintenance banner item.',
        'is_active' => true,
    ]);

    $response = $this->get('/');
    $response->assertOk()
        ->assertSee('Information banner item.')
        ->assertSee('Warning banner item.')
        ->assertSee('Maintenance banner item.')
        ->assertSee('border-amber-500/30', false)
        ->assertSee('border-rose-500/30', false);
});
