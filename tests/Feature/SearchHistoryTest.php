<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\SearchHistory;
use App\Models\User;
use App\Services\Ai\ConversationStore;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    config([
        'app.business_timezone' => 'Asia/Jakarta',
        'ai.driver' => 'fake',
        'ai.global_daily_cap' => 1000,
        'ai.free.pool_daily_limit' => 100,
        'ai.free.overbook_pct' => 100,
        'ai.free.waves' => [['time' => '00:00', 'share' => 100]],
        'plans.free.daily_units' => 5,
        'plans.free.title_cards' => 3,
        'plans.free.conversation_turns' => 8,
        'plans.free.ai_rate_limit_per_minute' => 6,
    ]);
    Cache::flush();
});

it('stores normal searches on the search page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/search?q=Inception')->assertOk();

    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(1);

    $entry = SearchHistory::where('user_id', $user->id)->first();
    expect($entry->type)->toBe('normal')
        ->and($entry->query)->toBe('Inception');
});

it('does not store live search typeahead queries', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/search?q=Incep&live=1')->assertOk();

    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(0);
});

it('stores only the first AI prompt truncated to 200 characters and never stores AI conversation content', function () {
    $user = User::factory()->create();
    $today = CarbonImmutable::now('Asia/Jakarta')->toDateString();
    DB::table('ai_free_pool')->insertOrIgnore(['used_on' => $today, 'taken' => 0]);
    DB::table('ai_free_places')->insertOrIgnore(['used_on' => $today, 'user_id' => $user->id, 'taken_at' => now()]);

    $longPrompt = 'A very long query about science fiction movies with time loops and paradoxes '.str_repeat('extra descriptive text ', 15);
    expect(mb_strlen($longPrompt))->toBeGreaterThan(200);

    // Turn 1: First AI prompt of the chat
    $firstResponse = $this->actingAs($user)->postJson('/ai/messages', [
        'message' => $longPrompt,
        'conversation_id' => null,
        'mode' => 'global',
    ])->assertOk();

    $conversationId = $firstResponse->json('data.conversation_id') ?? $firstResponse->json('conversation_id');
    $assistantReply = $firstResponse->json('data.reply') ?? $firstResponse->json('reply');

    // First prompt stored, truncated to 200 chars
    $entries = SearchHistory::where('user_id', $user->id)->get();
    expect($entries)->toHaveCount(1)
        ->and($entries->first()->type)->toBe('ai')
        ->and(mb_strlen($entries->first()->query))->toBe(200)
        ->and($entries->first()->query)->toBe(mb_substr($longPrompt, 0, 200));

    // Turn 2: Second message in the same conversation
    $this->actingAs($user)->postJson('/ai/messages', [
        'message' => 'Tell me more about the first candidate',
        'conversation_id' => $conversationId,
        'mode' => 'global',
    ])->assertOk();

    // Still only 1 entry in search_history
    expect(SearchHistory::where('user_id', $user->id)->count())->toBe(1);

    // AI conversation content (assistant replies, multi-turn history) is never stored in DB
    $allStoredQueries = SearchHistory::pluck('query')->all();
    expect($allStoredQueries)->not->toContain($assistantReply)
        ->and($allStoredQueries)->not->toContain('Tell me more about the first candidate');

    // Verify conversation turns live only in cache, not in database
    $cacheStore = app(ConversationStore::class);
    $loadedConversation = $cacheStore->load($user, $conversationId);
    expect($loadedConversation['history'])->not->toBeEmpty();
});

it('allows only the owner to read their search history', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);

    $ownerEntry = SearchHistory::create([
        'user_id' => $owner->id,
        'type' => 'normal',
        'query' => 'Secret Search Term By Owner',
    ]);

    // Guest redirected to login
    $this->get('/history')->assertRedirect(route('login'));

    // Owner can see their search history
    $this->actingAs($owner)->get('/history')
        ->assertOk()
        ->assertSee('Secret Search Term By Owner');

    // Other user cannot see owner search history on /history
    $this->actingAs($otherUser)->get('/history')
        ->assertOk()
        ->assertDontSee('Secret Search Term By Owner');

    // Other user cannot see owner search history via /history/recent
    $response = $this->actingAs($otherUser)->getJson('/history/recent')->assertOk();
    expect($response->json())->not->toContain('Secret Search Term By Owner');

    // Policy check: only the owner can view their search history record
    expect(Gate::forUser($owner)->allows('view', $ownerEntry))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('view', $ownerEntry))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('view', $ownerEntry))->toBeFalse();
});

it('clears all search history for the authenticated user without affecting others', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SearchHistory::create(['user_id' => $userA->id, 'type' => 'normal', 'query' => 'User A Search 1']);
    SearchHistory::create(['user_id' => $userA->id, 'type' => 'ai', 'query' => 'User A Search 2']);
    SearchHistory::create(['user_id' => $userB->id, 'type' => 'normal', 'query' => 'User B Search']);

    $this->actingAs($userA)->delete('/history')
        ->assertRedirect(route('history.index'));

    expect(SearchHistory::where('user_id', $userA->id)->count())->toBe(0)
        ->and(SearchHistory::where('user_id', $userB->id)->count())->toBe(1);
});

it('returns unique recent searches for the live search dropdown', function () {
    $user = User::factory()->create();

    SearchHistory::create(['user_id' => $user->id, 'type' => 'normal', 'query' => 'Matrix', 'created_at' => now()->subMinutes(10)]);
    SearchHistory::create(['user_id' => $user->id, 'type' => 'normal', 'query' => 'Matrix', 'created_at' => now()->subMinutes(5)]);
    SearchHistory::create(['user_id' => $user->id, 'type' => 'ai', 'query' => 'Interstellar', 'created_at' => now()->subMinutes(1)]);

    $response = $this->actingAs($user)->getJson('/history/recent')->assertOk();

    expect($response->json())->toBe(['Interstellar', 'Matrix']);
});

it('prunes search history older than 90 days', function () {
    $user = User::factory()->create();

    $oldRecord = SearchHistory::create([
        'user_id' => $user->id,
        'type' => 'normal',
        'query' => 'Old Movie Search',
        'created_at' => now()->subDays(95),
    ]);

    $recentRecord = SearchHistory::create([
        'user_id' => $user->id,
        'type' => 'normal',
        'query' => 'Recent Movie Search',
        'created_at' => now()->subDays(45),
    ]);

    $this->artisan('history:prune')->assertSuccessful();

    $this->assertDatabaseMissing('search_history', ['id' => $oldRecord->id]);
    $this->assertDatabaseHas('search_history', ['id' => $recentRecord->id]);
});

it('schedules history:prune daily at 03:40', function () {
    $schedule = app(Schedule::class);
    $events = collect($schedule->events());

    $event = $events->first(function ($event) {
        return str_contains((string) $event->command, 'history:prune');
    });

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('40 3 * * *');
});
