<?php

declare(strict_types=1);

use App\Contracts\MetadataProvider;
use App\DTOs\TitleData;
use App\Enums\MediaType;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\ClipLink;
use App\Models\Genre;
use App\Models\Title;
use App\Models\User;
use App\Models\Watchlist;
use Database\Seeders\GenreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['admin.path' => 'private-ops-test']);
    $this->seed(GenreSeeder::class);
    Storage::fake('public');

    $this->admin = User::factory()->create([
        'role' => Role::Admin,
        'email_verified_at' => now(),
    ]);

    $this->withSession(['admin_2fa_verified' => true])
        ->actingAs($this->admin, 'admin');
});

test('admin dashboard renders metrics and operational cards', function (): void {
    Title::factory()->create(['source' => 'tmdb', 'title' => 'Test Movie']);

    $response = $this->get('/private-ops-test');

    $response->assertOk()
        ->assertSee('Admin dashboard')
        ->assertSee('ADMIN')
        ->assertSee('Users')
        ->assertSee('Margin (Profit Meter)')
        ->assertSee('System Health');
});

test('upload validation rejects invalid MIME content and oversized files', function (): void {
    // 1. Text file masquerading as an image
    $fakeScript = UploadedFile::fake()->create('malicious.jpg', 50, 'text/plain');

    $response = $this->post('/private-ops-test/titles', [
        'title' => 'Malicious File Movie',
        'media_type' => 'movie',
        'poster' => $fakeScript,
    ]);

    $response->assertSessionHasErrors('poster');
    expect(Title::where('title', 'Malicious File Movie')->exists())->toBeFalse();

    // 2. Oversized file (> 2 MB)
    $oversized = UploadedFile::fake()->image('giant.jpg', 500, 500)->size(2500);

    $response2 = $this->post('/private-ops-test/titles', [
        'title' => 'Oversized Poster Movie',
        'media_type' => 'movie',
        'poster' => $oversized,
    ]);

    $response2->assertSessionHasErrors('poster');
    expect(Title::where('title', 'Oversized Poster Movie')->exists())->toBeFalse();
});

test('upload creates manual title with GD re-encoded WebP poster max 600px and 300KB', function (): void {
    // 1000x1500 JPEG image
    $validImage = UploadedFile::fake()->image('sample_poster.jpg', 1000, 1500);

    $genre = Genre::first();

    $response = $this->post('/private-ops-test/titles', [
        'title' => 'Indie Masterpiece',
        'original_title' => 'Karya Mandiri',
        'media_type' => 'movie',
        'release_year' => 2024,
        'runtime_minutes' => 110,
        'overview' => 'A wonderful independent film.',
        'genres' => [$genre->id],
        'poster' => $validImage,
    ]);

    $response->assertRedirect('/private-ops-test/titles');

    $title = Title::where('title', 'Indie Masterpiece')->first();
    expect($title)->not->toBeNull()
        ->and($title->source)->toBe('manual')
        ->and($title->tmdb_id)->toBeNull()
        ->and($title->poster_path)->toStartWith('local:posters/')
        ->and($title->poster_path)->toEndWith('.webp');

    $diskPath = substr($title->poster_path, 6);
    Storage::disk('public')->assertExists($diskPath);

    // Verify stored file size <= 300 KB
    $storedBytes = strlen((string) Storage::disk('public')->get($diskPath));
    expect($storedBytes)->toBeLessThanOrEqual(300 * 1024);

    // Verify dimensions <= 600px width
    $fullPath = Storage::disk('public')->path($diskPath);
    $img = imagecreatefromwebp($fullPath);
    expect($img)->not->toBeFalse();
    expect(imagesx($img))->toBeLessThanOrEqual(600);
});

test('locked_fields recorded on edit and respected by refresh', function (): void {
    $title = Title::factory()->create([
        'tmdb_id' => 77777,
        'source' => 'tmdb',
        'media_type' => 'movie',
        'title' => 'TMDB Title Before Edit',
        'overview' => 'Original TMDB Overview',
        'locked_fields' => null,
    ]);

    // Admin edits overview and title
    $response = $this->put("/private-ops-test/titles/{$title->id}", [
        'title' => 'TMDB Title Before Edit', // unchanged
        'overview' => 'Custom Admin Curated Synopsis', // changed!
        'media_type' => 'movie',
    ]);

    $response->assertRedirect("/private-ops-test/titles/{$title->id}/edit");

    $title->refresh();
    expect($title->locked_fields)->toContain('overview')
        ->and($title->overview)->toBe('Custom Admin Curated Synopsis');

    // Mock TMDB provider returning fresh details
    $mockProvider = Mockery::mock(MetadataProvider::class);
    $mockProvider->shouldReceive('refreshDetails')
        ->with(77777, MediaType::Movie)
        ->andReturn(new TitleData(
            tmdbId: 77777,
            type: MediaType::Movie,
            title: 'New TMDB Title From API',
            originalTitle: 'Original',
            overview: 'Incoming API Overview That Should Be Skipped',
            releaseYear: 2023,
            posterPath: '/new_poster.jpg',
            backdropPath: null,
            voteAverage: 8.0,
            directors: [],
            cast: [],
            genreIds: [],
            videos: [],
            adult: false,
        ));
    $this->app->instance(MetadataProvider::class, $mockProvider);

    // Trigger refresh
    $refreshResponse = $this->post("/private-ops-test/titles/{$title->id}/refresh");
    $refreshResponse->assertRedirect();

    $title->refresh();
    // overview was locked -> preserved!
    expect($title->overview)->toBe('Custom Admin Curated Synopsis')
        // title was not locked -> updated by TMDB!
        ->and($title->title)->toBe('New TMDB Title From API');
});

test('mutations write audit logs', function (): void {
    // 1. Create manual title
    $this->post('/private-ops-test/titles', [
        'title' => 'Audit Test Movie',
        'media_type' => 'movie',
    ]);
    $created = Title::where('title', 'Audit Test Movie')->firstOrFail();
    expect(AuditLog::where('action', 'title.create')->where('subject_id', $created->id)->exists())->toBeTrue();

    // 2. Update title
    $this->put("/private-ops-test/titles/{$created->id}", [
        'title' => 'Audit Test Movie Edited',
        'media_type' => 'movie',
    ]);
    expect(AuditLog::where('action', 'title.update')->where('subject_id', $created->id)->exists())->toBeTrue();

    // 3. Toggle hide / unhide
    $this->post("/private-ops-test/titles/{$created->id}/toggle-hide");
    expect(AuditLog::where('action', 'title.hide')->where('subject_id', $created->id)->exists())->toBeTrue();

    $this->post("/private-ops-test/titles/{$created->id}/toggle-hide");
    expect(AuditLog::where('action', 'title.unhide')->where('subject_id', $created->id)->exists())->toBeTrue();

    // 4. Delete title
    $this->delete("/private-ops-test/titles/{$created->id}");
    expect(AuditLog::where('action', 'title.delete')->where('subject_id', $created->id)->exists())->toBeTrue();
});

test('title changes are visible within 60 s via cache invalidation', function (): void {
    // Warm up landing page cache
    Cache::put('v1:landing:posters', [
        ['title' => 'Old Title Cached', 'poster_path' => '/old.jpg'],
    ], 86400);

    expect(Cache::has('v1:landing:posters'))->toBeTrue();

    // Create a manual title
    $this->post('/private-ops-test/titles', [
        'title' => 'Brand New Title',
        'media_type' => 'movie',
    ]);

    // Cache key must have been invalidated immediately
    expect(Cache::has('v1:landing:posters'))->toBeFalse();

    // Visiting landing page computes fresh data immediately
    $landingResponse = $this->get('/');
    $landingResponse->assertOk()
        ->assertSee('Brand New Title');
});

test('title filtering by source, hidden, and stale works', function (): void {
    $tmdbTitle = Title::factory()->create([
        'source' => 'tmdb',
        'title' => 'TMDB Title Fresh',
        'is_hidden' => false,
        'synced_at' => now(),
    ]);

    $staleTitle = Title::factory()->create([
        'source' => 'tmdb',
        'title' => 'TMDB Title Stale',
        'is_hidden' => false,
        'synced_at' => now()->subDays(160),
    ]);

    $manualTitle = Title::factory()->create([
        'source' => 'manual',
        'title' => 'Manual Title Hidden',
        'is_hidden' => true,
        'synced_at' => null,
    ]);

    // Filter source=manual
    $resSource = $this->get('/private-ops-test/titles?source=manual');
    $resSource->assertOk()
        ->assertSee('Manual Title Hidden')
        ->assertDontSee('TMDB Title Fresh');

    // Filter hidden=1
    $resHidden = $this->get('/private-ops-test/titles?hidden=1');
    $resHidden->assertOk()
        ->assertSee('Manual Title Hidden')
        ->assertDontSee('TMDB Title Fresh');

    // Filter stale=1
    $resStale = $this->get('/private-ops-test/titles?stale=1');
    $resStale->assertOk()
        ->assertSee('TMDB Title Stale')
        ->assertDontSee('TMDB Title Fresh');
});

test('delete shows confirmation with dependency summary', function (): void {
    $title = Title::factory()->create(['title' => 'Title With Dependencies']);
    $user = User::factory()->create();

    Watchlist::create(['user_id' => $user->id, 'title_id' => $title->id]);
    ClipLink::create([
        'title_id' => $title->id,
        'video_platform' => 'youtube',
        'video_id' => 'abc123xyz',
        'url' => 'https://www.youtube.com/watch?v=abc123xyz',
        'trust' => 'official',
        'source' => 'admin',
        'status' => 'active',
    ]);

    $confirmResponse = $this->get("/private-ops-test/titles/{$title->id}/delete");

    $confirmResponse->assertOk()
        ->assertSee('Delete Title Confirmation')
        ->assertSee('Watchlist Entries')
        ->assertSee('Clip Links')
        ->assertSee('Confirm &amp; Delete Permanently', false);

    $deleteResponse = $this->delete("/private-ops-test/titles/{$title->id}");
    $deleteResponse->assertRedirect('/private-ops-test/titles');

    expect(Title::find($title->id))->toBeNull()
        ->and(Watchlist::where('title_id', $title->id)->count())->toBe(0)
        ->and(ClipLink::where('title_id', $title->id)->count())->toBe(0);
});
