<?php

declare(strict_types=1);

use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Models\ChannelAllowlist;
use App\Models\ClipLink;
use App\Models\Title;
use App\Services\Clips\ChannelPolicy;
use App\Services\Clips\ClipFinderChain;
use App\Services\Clips\CuratedClipFinder;
use App\Services\Clips\PlatformUrl;
use App\Services\Clips\SearchLinkFinder;
use App\Services\Clips\StoredClipFinder;
use App\Services\Clips\YoutubeOembed;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('allows only canonical YouTube video URLs with strict hosts and IDs', function () {
    expect(PlatformUrl::parse('https://youtu.be/AbCdEf123_-?t=42')?->canonicalUrl)
        ->toBe('https://www.youtube.com/watch?v=AbCdEf123_-')
        ->and(PlatformUrl::parse('https://youtube.com.evil.test/watch?v=AbCdEf123_-'))->toBeNull()
        ->and(PlatformUrl::parse('https://youtube.com@127.0.0.1/watch?v=AbCdEf123_-'))->toBeNull()
        ->and(PlatformUrl::parse('http://youtube.com/watch?v=AbCdEf123_-'))->toBeNull()
        ->and(PlatformUrl::parse('https://youtube.com/watch?v=AbCdEf123_-%26'))->toBeNull();
});

it('checks YouTube oEmbed only at its fixed endpoint and rejects non-200 responses', function () {
    Http::preventStrayRequests();
    Http::fake(['https://www.youtube.com/oembed*' => Http::response([], 404)]);

    $validUrl = PlatformUrl::parse('https://www.youtube.com/watch?v=AbCdEf123_-');
    $invalidUrl = PlatformUrl::parse('https://youtube.com.evil.test/watch?v=AbCdEf123_-');

    expect($validUrl)->toBeInstanceOf(PlatformUrl::class)
        ->and(app(YoutubeOembed::class)->check($validUrl))->toBeNull()
        ->and($invalidUrl)->toBeNull();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://www.youtube.com/oembed?url=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3DAbCdEf123_-&format=json');
});

it('rejects blocked channels and marks trusted channels embeddable', function () {
    ChannelAllowlist::query()->create(['platform' => 'youtube', 'channel_id' => 'UCblocked', 'channel_name' => 'Blocked', 'trust' => 'blocked']);
    ChannelAllowlist::query()->create(['platform' => 'youtube', 'channel_id' => 'UCtrusted', 'channel_name' => 'Trusted', 'trust' => 'trusted']);
    $policy = app(ChannelPolicy::class);

    expect($policy->assess('youtube', 'UCblocked', 'Blocked'))->toBe(['allowed' => false, 'embeddable' => false, 'trust' => 'blocked'])
        ->and($policy->assess('youtube', 'UCtrusted', 'Trusted'))->toBe(['allowed' => true, 'embeddable' => true, 'trust' => 'trusted'])
        ->and($policy->assess('youtube', null, 'Unknown'))->toBe(['allowed' => true, 'embeddable' => false, 'trust' => null]);
});

it('returns active admin-curated clips before stored clips and fallback links', function () {
    $title = Title::factory()->create();
    $scene = new SceneQuery(str_repeat('a', 40), keywords: ['finale']);
    $curated = ClipLink::query()->create([
        'title_id' => $title->id,
        'video_platform' => 'youtube',
        'url' => 'https://www.youtube.com/watch?v=Curated1234',
        'source' => 'admin',
        'status' => 'active',
        'trust' => 'official',
    ]);
    ClipLink::query()->create([
        'title_id' => $title->id,
        'video_platform' => 'youtube',
        'url' => 'https://www.youtube.com/watch?v=Stored123456',
        'source' => 'resolver',
        'scene_key' => $scene->sceneKey,
        'status' => 'active',
        'verified_at' => now(),
    ]);
    $chain = new ClipFinderChain([new CuratedClipFinder, new StoredClipFinder, new SearchLinkFinder]);

    $results = $chain->find($title, $scene, Plan::Free);

    expect($results->clips)->toHaveCount(1)
        ->and($results->clips[0]->is($curated))->toBeTrue()
        ->and($results->verified)->toBeTrue();
});

it('returns only recently verified stored clips when no curated clip exists', function () {
    $title = Title::factory()->create();
    $scene = new SceneQuery(str_repeat('b', 40), keywords: ['finale']);
    $stored = ClipLink::query()->create([
        'title_id' => $title->id,
        'video_platform' => 'youtube',
        'url' => 'https://www.youtube.com/watch?v=Stored123456',
        'source' => 'resolver',
        'scene_key' => $scene->sceneKey,
        'status' => 'active',
        'verified_at' => now(),
    ]);
    ClipLink::query()->create([
        'title_id' => $title->id,
        'video_platform' => 'youtube',
        'url' => 'https://www.youtube.com/watch?v=Expired1234',
        'source' => 'resolver',
        'scene_key' => $scene->sceneKey,
        'status' => 'active',
        'verified_at' => now()->subDays(31),
    ]);
    $chain = new ClipFinderChain([new CuratedClipFinder, new StoredClipFinder, new SearchLinkFinder]);

    $results = $chain->find($title, $scene, Plan::Free);

    expect($results->clips)->toHaveCount(1)
        ->and($results->clips[0]->is($stored))->toBeTrue()
        ->and($results->verified)->toBeTrue();
});

it('keeps search fallback links unverified', function () {
    $title = new Title(['title' => 'Example Film', 'release_year' => 2024]);
    $results = (new ClipFinderChain([new SearchLinkFinder]))->find($title, new SceneQuery('scene-key', keywords: ['finale']), Plan::Free);

    expect($results->verified)->toBeFalse()
        ->and($results->clips[0]['verified'])->toBeFalse()
        ->and($results->clips[0]['trust'])->toBe('unverified')
        ->and($results->clips[0]['url'])->toContain('youtube.com/results?search_query=');
});
