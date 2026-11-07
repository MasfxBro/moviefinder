<?php

declare(strict_types=1);

use App\Models\Title;
use App\Models\User;
use App\Models\Watchlist;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('keeps watchlist items private and does not expose another user titles', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $title = Title::factory()->movie()->create();
    Watchlist::create(['user_id' => $owner->id, 'title_id' => $title->id]);

    $this->actingAs($otherUser)->get(route('watchlist.index'))
        ->assertOk()
        ->assertDontSee($title->title);

    $this->actingAs($otherUser)->post(route('watchlist.toggle', $title))
        ->assertOk()
        ->assertJsonPath('action', 'added');

    expect(Watchlist::where('user_id', $otherUser->id)->where('title_id', $title->id)->exists())->toBeTrue()
        ->and(Watchlist::where('user_id', $owner->id)->where('title_id', $title->id)->exists())->toBeTrue();
});

it('rejects unverified users from reading or changing their watchlist', function () {
    $user = User::factory()->unverified()->create();
    $title = Title::factory()->movie()->create();

    $this->actingAs($user)->get(route('watchlist.index'))->assertForbidden();
    $this->actingAs($user)->post(route('watchlist.toggle', $title))->assertForbidden();

    expect(Watchlist::count())->toBe(0);
});

it('enforces the configured plan watchlist cap', function () {
    $user = User::factory()->create();
    $title = Title::factory()->movie()->create();
    $limit = config('plans.free.watchlist_max');
    $titles = Title::factory()->count($limit)->create();
    foreach ($titles as $savedTitle) {
        Watchlist::create(['user_id' => $user->id, 'title_id' => $savedTitle->id]);
    }

    $this->actingAs($user)->post(route('watchlist.toggle', $title))
        ->assertUnprocessable()
        ->assertJsonPath('message', "Your watchlist is full ({$limit} titles).");

    expect(Watchlist::where('user_id', $user->id)->count())->toBe($limit);
});

it('removes an owned title and can add it back by toggling again', function () {
    $user = User::factory()->create();
    $title = Title::factory()->movie()->create();
    Watchlist::create(['user_id' => $user->id, 'title_id' => $title->id]);

    $this->actingAs($user)->post(route('watchlist.toggle', $title))
        ->assertOk()
        ->assertJsonPath('action', 'removed');

    $this->assertDatabaseMissing('watchlists', ['user_id' => $user->id, 'title_id' => $title->id]);

    $this->actingAs($user)->post(route('watchlist.toggle', $title))
        ->assertOk()
        ->assertJsonPath('action', 'added');

    $this->assertDatabaseHas('watchlists', ['user_id' => $user->id, 'title_id' => $title->id]);
});

it('enforces unique user and title pairs in the database', function () {
    $user = User::factory()->create();
    $title = Title::factory()->movie()->create();
    Watchlist::create(['user_id' => $user->id, 'title_id' => $title->id]);

    expect(fn () => DB::table('watchlists')->insert([
        'user_id' => $user->id,
        'title_id' => $title->id,
        'created_at' => now(),
    ]))->toThrow(QueryException::class);
});
