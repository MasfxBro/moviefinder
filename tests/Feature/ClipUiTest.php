<?php

declare(strict_types=1);

use App\Models\ClipLink;
use App\Models\LinkReport;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('report-link');
});

test('embeds only for official and trusted youtube clips', function () {
    $official = ClipLink::factory()->create([
        'video_platform' => 'youtube',
        'video_id' => 'abc12345678',
        'trust' => 'official',
        'status' => 'active',
    ]);

    $trusted = ClipLink::factory()->create([
        'video_platform' => 'youtube',
        'video_id' => 'def12345678',
        'trust' => 'trusted',
        'status' => 'active',
    ]);

    $unverified = ClipLink::factory()->unverified()->create([
        'video_platform' => 'youtube',
        'video_id' => 'ghi12345678',
        'status' => 'active',
    ]);

    $external = ClipLink::factory()->external()->create([
        'status' => 'active',
    ]);

    expect($official->isEmbeddable())->toBeTrue()
        ->and($trusted->isEmbeddable())->toBeTrue()
        ->and($unverified->isEmbeddable())->toBeFalse()
        ->and($external->isEmbeddable())->toBeFalse();

    expect($official->sourceLabel())->toBe('Official')
        ->and($trusted->sourceLabel())->toBe('Trusted')
        ->and($unverified->sourceLabel())->toBe('Unverified')
        ->and($external->sourceLabel())->toBe('External link');

    // UI rendering tests
    $officialHtml = (string) $this->blade('<x-clip-card :clip="$clip" />', ['clip' => $official]);
    expect($officialHtml)->toContain('Official')
        ->and($officialHtml)->toContain('Preview (first 1:00)')
        ->and($officialHtml)->toContain('Watch full video');

    $unverifiedHtml = (string) $this->blade('<x-clip-card :clip="$clip" />', ['clip' => $unverified]);
    expect($unverifiedHtml)->toContain('Unverified')
        ->and($unverifiedHtml)->toContain('Watch full video');

    $externalHtml = (string) $this->blade('<x-clip-card :clip="$clip" />', ['clip' => $external]);
    expect($externalHtml)->toContain('External link')
        ->and($externalHtml)->toContain('Watch full video');
});

test('reported links are not embedded', function () {
    $clip = ClipLink::factory()->create([
        'video_platform' => 'youtube',
        'video_id' => 'xyz12345678',
        'trust' => 'official',
        'status' => 'active',
    ]);

    expect($clip->isEmbeddable())->toBeTrue();

    $clip->update(['status' => 'reported']);

    expect($clip->fresh()->isEmbeddable())->toBeFalse();

    $html = (string) $this->blade('<x-clip-card :clip="$clip" />', ['clip' => $clip->fresh()]);
    expect($html)->toContain('Reported');
});

test('user can report a clip link and its status becomes reported', function () {
    $user = User::factory()->create();
    $clip = ClipLink::factory()->create([
        'status' => 'active',
        'trust' => 'official',
    ]);

    $response = $this->actingAs($user)->postJson(route('clips.report', $clip), [
        'reason' => 'dead',
        'details' => 'Video returns 404 on YouTube.',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'reported',
        ]);

    expect($clip->fresh()->status)->toBe('reported')
        ->and($clip->fresh()->isEmbeddable())->toBeFalse();

    $this->assertDatabaseHas('link_reports', [
        'clip_link_id' => $clip->id,
        'user_id' => $user->id,
        'reason' => 'dead',
        'details' => 'Video returns 404 on YouTube.',
        'status' => 'open',
    ]);
});

test('report link enforces daily limit of 10 reports per user', function () {
    $user = User::factory()->create();

    // Submit 10 reports
    for ($i = 1; $i <= 10; $i++) {
        $clip = ClipLink::factory()->create(['status' => 'active']);
        $response = $this->actingAs($user)->postJson(route('clips.report', $clip), [
            'reason' => 'wrong_scene',
        ]);
        $response->assertOk();
    }

    expect(LinkReport::query()->where('user_id', $user->id)->count())->toBe(10);

    // 11th report should be rejected with 429
    $eleventhClip = ClipLink::factory()->create(['status' => 'active']);
    $response = $this->actingAs($user)->postJson(route('clips.report', $eleventhClip), [
        'reason' => 'copyright',
    ]);

    $response->assertStatus(429);
    expect($eleventhClip->fresh()->status)->toBe('active');

    // Another user can still submit their reports
    $anotherUser = User::factory()->create();
    $anotherResponse = $this->actingAs($anotherUser)->postJson(route('clips.report', $eleventhClip), [
        'reason' => 'copyright',
    ]);
    $anotherResponse->assertOk();
    expect($eleventhClip->fresh()->status)->toBe('reported');
});

test('no clip found component renders search on youtube link and where to watch', function () {
    $html = (string) $this->blade('<x-no-clip-found title="Inception" query="Inception rotating hallway scene" />');

    expect($html)->toContain('No clip found')
        ->and($html)->toContain('Search on YouTube')
        ->and($html)->toContain('youtube.com/results?search_query=')
        ->and($html)->toContain('Where to watch')
        ->and($html)->toContain('#where-to-watch');
});
