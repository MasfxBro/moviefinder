<?php

use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\ChannelAllowlist;
use App\Models\ClipLink;
use App\Models\LinkReport;
use App\Models\Title;
use App\Models\User;
use App\Services\Clips\CuratedClipFinder;

beforeEach(function () {
    config(['admin.path' => 'private-ops-test']);
});

test('blocked channel never appears in clip finders', function () {
    $title = Title::factory()->create();
    $clip = ClipLink::factory()->create([
        'title_id' => $title->id,
        'source' => 'admin',
        'status' => 'active',
        'channel_name' => 'Bad Channel',
    ]);

    // Add channel to allowlist as blocked
    ChannelAllowlist::create([
        'platform' => 'youtube',
        'channel_name' => 'Bad Channel',
        'trust' => 'blocked',
    ]);

    $finder = app(CuratedClipFinder::class);
    $result = $finder->find($title, new SceneQuery('key', 'Test description', []), Plan::Free);

    expect($result->clips)->toBeEmpty();
});

test('restored link is embeddable again and creates audit log', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $title = Title::factory()->create();
    $clip = ClipLink::factory()->create([
        'title_id' => $title->id,
        'status' => 'reported',
        'video_platform' => 'youtube',
        'video_id' => 'abc123xyz',
        'trust' => 'trusted',
    ]);

    $report = LinkReport::create([
        'clip_link_id' => $clip->id,
        'user_id' => User::factory()->create()->id,
        'reason' => 'dead',
        'status' => 'open',
    ]);

    $response = $this->withSession(['admin_2fa_verified' => true])
        ->actingAs($admin, 'admin')
        ->post("/private-ops-test/reports/{$report->id}/restore");

    $response->assertRedirect();
    expect($clip->fresh()->status)->toBe('active');
    expect($clip->fresh()->is_embeddable)->toBeTrue();
    expect($report->fresh()->status)->toBe('resolved');

    expect(AuditLog::where('action', 'link_report.restore')->exists())->toBeTrue();
});

test('admin clip moderation CRUD and bulk remove write audit logs', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $title = Title::factory()->create();

    $this->withSession(['admin_2fa_verified' => true])->actingAs($admin, 'admin');

    // Store clip link
    $response = $this->post('/private-ops-test/clip-links', [
        'title_id' => $title->id,
        'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'label' => 'Test Clip',
        'trust' => 'trusted',
    ]);

    $response->assertRedirect();
    expect(ClipLink::where('url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')->exists())->toBeTrue();
    expect(AuditLog::where('action', 'clip_link.create')->exists())->toBeTrue();

    // Bulk remove by channel
    $clip = ClipLink::first();
    $clip->update(['channel_name' => 'RickAstleyVEVO', 'status' => 'active']);

    $response = $this->post('/private-ops-test/clip-links/bulk-remove', [
        'channel_name' => 'RickAstleyVEVO',
    ]);

    $response->assertRedirect();
    expect($clip->fresh()->status)->toBe('removed');
    expect(AuditLog::where('action', 'clip_link.bulk_remove')->exists())->toBeTrue();
});
