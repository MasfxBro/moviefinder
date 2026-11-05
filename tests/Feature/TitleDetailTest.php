<?php

declare(strict_types=1);

use App\Contracts\MetadataProvider;
use App\DTOs\TitleData;
use App\Enums\MediaType;
use App\Models\Genre;
use App\Models\Title;
use App\Models\TitlePerson;
use App\Models\User;
use Database\Seeders\GenreSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(GenreSeeder::class);
    $this->user = User::factory()->create();
});

test('guest is redirected to login from title detail', function () {
    $title = Title::factory()->movie()->create([
        'title' => 'Inception',
        'slug' => 'inception-2010',
    ]);

    $this->get('/titles/inception-2010')
        ->assertRedirect(route('login'));
});

test('adult title returns 404', function () {
    // Model boot blocks adult=true directly, so update DB directly
    $title = Title::factory()->movie()->create([
        'title' => 'Adult Title',
        'slug' => 'adult-title',
    ]);

    DB::table('titles')
        ->where('id', $title->id)
        ->update(['adult' => true]);

    $this->actingAs($this->user)
        ->get('/titles/adult-title')
        ->assertNotFound();
});

test('hidden title returns 404', function () {
    $title = Title::factory()->movie()->create([
        'title' => 'Hidden Title',
        'slug' => 'hidden-title',
        'is_hidden' => true,
    ]);

    $this->actingAs($this->user)
        ->get('/titles/hidden-title')
        ->assertNotFound();
});

test('missing fields are hidden and never shown as null', function () {
    $title = Title::factory()->movie()->create([
        'title' => 'Minimal Title',
        'slug' => 'minimal-title',
        'original_title' => null,
        'tagline' => null,
        'runtime_minutes' => null,
        'overview' => null,
        'poster_path' => null,
        'backdrop_path' => null,
        'trailer_youtube_key' => null,
        'vote_average' => 0.0,
        'vote_count' => 0,
        'original_language' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->get('/titles/minimal-title');

    $response->assertOk()
        ->assertSee('Minimal Title')
        ->assertDontSee('null')
        ->assertDontSee('Original title:')
        ->assertDontSee('Synopsis');
});

test('title detail renders metadata chips, cast, synopsis, and reserved slots', function () {
    $action = Genre::find(28);
    $title = Title::factory()->movie()->create([
        'title' => 'The Dark Knight',
        'slug' => 'the-dark-knight-2008',
        'release_year' => 2008,
        'runtime_minutes' => 152,
        'vote_average' => 9.0,
        'vote_count' => 25000,
        'overview' => 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.',
        'tagline' => 'Why so serious?',
        'trailer_youtube_key' => 'EXeTwQWrcwY',
    ]);

    $title->genres()->attach($action->id);

    TitlePerson::create([
        'title_id' => $title->id,
        'name' => 'Christopher Nolan',
        'role' => 'director',
        'position' => 1,
    ]);

    TitlePerson::create([
        'title_id' => $title->id,
        'name' => 'Christian Bale',
        'role' => 'cast',
        'position' => 1,
    ]);

    $response = $this->actingAs($this->user)
        ->get('/titles/the-dark-knight-2008');

    $response->assertOk()
        ->assertSee('The Dark Knight')
        ->assertSee('Why so serious?')
        ->assertSee('2h 32m')
        ->assertSee('9.0')
        ->assertSee('Action')
        ->assertSee('Directed by')
        ->assertSee('Christopher Nolan')
        ->assertSee('Top Cast')
        ->assertSee('Christian Bale')
        ->assertSee('Synopsis')
        ->assertSee('Where to watch')
        ->assertSee('Trailer')
        ->assertSee('watchlist-slot', false)
        ->assertSee('where-to-watch-slot', false)
        ->assertSee('trailer-slot', false)
        ->assertSee('title-chat-slot', false)
        ->assertSee('What would you like to know about The Dark Knight?');
});

test('unknown tmdb-only result imports once then redirects to slug', function () {
    $fakeMetadata = mock(MetadataProvider::class);
    $fakeMetadata->shouldReceive('details')
        ->with(12345, MediaType::Movie)
        ->once()
        ->andReturn(new TitleData(
            tmdbId: 12345,
            title: 'Imported TMDB Movie',
            originalTitle: 'Imported Original',
            type: MediaType::Movie,
            releaseYear: 2024,
            posterPath: '/path.jpg',
            backdropPath: '/bg.jpg',
            overview: 'Imported overview.',
            tagline: 'Imported tagline.',
            runtimeMinutes: 120,
            voteAverage: 7.5,
            voteCount: 100,
            popularity: 88.0,
            originalLanguage: 'en',
            genreIds: [28],
            directors: ['Jane Director'],
            cast: ['John Actor'],
            videos: ['abc12345'],
            adult: false,
        ));

    app()->instance(MetadataProvider::class, $fakeMetadata);

    $response = $this->actingAs($this->user)
        ->get('/titles/tmdb-12345');

    $imported = Title::where('tmdb_id', 12345)->first();
    expect($imported)->not->toBeNull()
        ->and($imported->title)->toBe('Imported TMDB Movie');

    $response->assertRedirect(route('titles.show', ['slug' => $imported->slug]));

    // Opening again redirects directly without calling metadata provider again
    $secondResponse = $this->actingAs($this->user)
        ->get('/titles/tmdb-12345');

    $secondResponse->assertRedirect(route('titles.show', ['slug' => $imported->slug]));
});
