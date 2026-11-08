<?php

declare(strict_types=1);

use App\Contracts\PlanResolver;
use App\Enums\Plan;
use App\Enums\RequestStatus;
use App\Models\Title;
use App\Models\TitleRequest;
use App\Models\User;
use App\Notifications\RequestResolved;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('request-title');
});

test('guests are redirected to login and returned to requests/create after authentication', function () {
    $user = User::factory()->create();

    $response = $this->get('/requests/create');
    $response->assertRedirect(route('login'));

    $loginResponse = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $loginResponse->assertRedirect('/requests/create');
});

test('guests are redirected to login when visiting requests index', function () {
    $this->get('/requests')->assertRedirect(route('login'));
});

test('search page not-found state renders prefilled request button', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/search?q=Interstellar');

    $response->assertOk()
        ->assertSee('We could not find that title yet')
        ->assertSee('Request this title')
        ->assertSee('/requests/create?title=Interstellar');
});

test('requests create page prefills title from query string', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/requests/create?title=Blade+Runner');

    $response->assertOk()
        ->assertSee('value="Blade Runner"', false);
});

test('user can submit a title request and view it on my requests page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/requests', [
        'title' => 'The Matrix Reloaded',
        'type' => 'movie',
        'year' => 2003,
        'note' => 'Please add the remastered edition.',
    ]);

    $response->assertRedirect(route('requests.index'));
    $this->assertDatabaseHas('title_requests', [
        'user_id' => $user->id,
        'title_text' => 'The Matrix Reloaded',
        'media_type' => 'movie',
        'release_year' => 2003,
        'status' => 'pending',
    ]);

    $indexResponse = $this->actingAs($user)->get('/requests');
    $indexResponse->assertOk()
        ->assertSee('The Matrix Reloaded')
        ->assertSee('2003')
        ->assertSee('pending');
});

test('enforces per-plan daily limit of 2 for free users', function () {
    $user = User::factory()->create();

    // 1st request
    $this->actingAs($user)->post('/requests', [
        'title' => 'Film One',
        'year' => 2001,
    ])->assertRedirect(route('requests.index'));

    // 2nd request
    $this->actingAs($user)->post('/requests', [
        'title' => 'Film Two',
        'year' => 2002,
    ])->assertRedirect(route('requests.index'));

    expect(TitleRequest::where('user_id', $user->id)->count())->toBe(2);

    // 3rd request reaches limit -> 429
    $response = $this->actingAs($user)->post('/requests', [
        'title' => 'Film Three',
        'year' => 2003,
    ]);

    $response->assertStatus(429);
});

test('enforces per-plan daily limit of 5 for plus users', function () {
    $user = User::factory()->create();
    $resolver = Mockery::mock(PlanResolver::class);
    $resolver->shouldReceive('resolve')->andReturn(Plan::Plus);
    app()->instance(PlanResolver::class, $resolver);

    for ($i = 1; $i <= 5; $i++) {
        $this->actingAs($user)->post('/requests', [
            'title' => 'Plus Film '.$i,
            'year' => 2010 + $i,
        ])->assertRedirect(route('requests.index'));
    }

    expect(TitleRequest::where('user_id', $user->id)->count())->toBe(5);

    // 6th request fails with 429
    $this->actingAs($user)->post('/requests', [
        'title' => 'Plus Film 6',
        'year' => 2020,
    ])->assertStatus(429);
});

test('enforces per-plan daily limit of 10 for pro users', function () {
    $user = User::factory()->create();
    $resolver = Mockery::mock(PlanResolver::class);
    $resolver->shouldReceive('resolve')->andReturn(Plan::Pro);
    app()->instance(PlanResolver::class, $resolver);

    for ($i = 1; $i <= 10; $i++) {
        $this->actingAs($user)->post('/requests', [
            'title' => 'Pro Film '.$i,
            'year' => 2010 + $i,
        ])->assertRedirect(route('requests.index'));
    }

    expect(TitleRequest::where('user_id', $user->id)->count())->toBe(10);

    // 11th request fails with 429
    $this->actingAs($user)->post('/requests', [
        'title' => 'Pro Film 11',
        'year' => 2030,
    ])->assertStatus(429);
});

test('blocks duplicate title requests by title and year', function () {
    $user = User::factory()->create();

    TitleRequest::factory()->create([
        'title_text' => 'Inception',
        'release_year' => 2010,
    ]);

    // Submitting duplicate with exact casing and year
    $response = $this->actingAs($user)->post('/requests', [
        'title' => 'Inception',
        'year' => 2010,
    ]);

    $response->assertSessionHasErrors(['title']);

    // Submitting duplicate with different casing
    $caseResponse = $this->actingAs($user)->post('/requests', [
        'title' => 'inception',
        'year' => 2010,
    ]);
    $caseResponse->assertSessionHasErrors(['title']);

    // Same title with null year when null year already exists
    $user2 = User::factory()->create();

    TitleRequest::factory()->create([
        'title_text' => 'Unknown Movie',
        'release_year' => null,
    ]);

    $nullYearResponse = $this->actingAs($user2)->post('/requests', [
        'title' => 'Unknown Movie',
        'year' => null,
    ]);
    $nullYearResponse->assertSessionHasErrors(['title']);

    // Same title with different year is allowed
    $diffYearResponse = $this->actingAs($user2)->post('/requests', [
        'title' => 'Inception',
        'year' => 2012,
    ]);
    $diffYearResponse->assertRedirect(route('requests.index'));
});

test('prevents IDOR: users cannot view another users title request', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $requestA = TitleRequest::factory()->create([
        'user_id' => $userA->id,
        'title_text' => 'Private Title of User A',
    ]);

    // User B accessing User A's request directly -> 403 Forbidden
    $response = $this->actingAs($userB)->get('/requests/'.$requestA->id);
    $response->assertForbidden();

    // User B viewing the requests list -> does not see User A's request
    $listResponse = $this->actingAs($userB)->get('/requests');
    $listResponse->assertOk()
        ->assertDontSee('Private Title of User A');

    // User A can view their own request
    $ownResponse = $this->actingAs($userA)->get('/requests/'.$requestA->id);
    $ownResponse->assertOk()
        ->assertSee('Private Title of User A');
});

test('displays statuses correctly with admin notes and resolved titles', function () {
    $user = User::factory()->create();

    $title = Title::factory()->create(['title' => 'Resolved Film', 'slug' => 'resolved-film-2020']);

    $pending = TitleRequest::factory()->create([
        'user_id' => $user->id,
        'title_text' => 'Pending Film',
        'status' => RequestStatus::Pending,
    ]);

    $approved = TitleRequest::factory()->approved()->create([
        'user_id' => $user->id,
        'title_text' => 'Approved Film',
    ]);

    $added = TitleRequest::factory()->added()->create([
        'user_id' => $user->id,
        'title_text' => 'Added Film',
        'resolved_title_id' => $title->id,
    ]);

    $rejected = TitleRequest::factory()->rejected('Not available upstream')->create([
        'user_id' => $user->id,
        'title_text' => 'Rejected Film',
    ]);

    $response = $this->actingAs($user)->get('/requests');
    $response->assertOk()
        ->assertSee('Pending Film')
        ->assertSee('pending')
        ->assertSee('Approved Film')
        ->assertSee('approved')
        ->assertSee('Added Film')
        ->assertSee('added')
        ->assertSee('/titles/resolved-film-2020')
        ->assertSee('Rejected Film')
        ->assertSee('rejected')
        ->assertSee('Not available upstream');
});

test('sends RequestResolved notification when request is resolved', function () {
    Notification::fake();

    $user = User::factory()->create();
    $title = Title::factory()->create(['title' => 'Dune Part Two', 'slug' => 'dune-part-two-2024']);
    $request = TitleRequest::factory()->create([
        'user_id' => $user->id,
        'title_text' => 'Dune Part Two',
        'status' => RequestStatus::Pending,
    ]);

    $request->resolve(RequestStatus::Added, 'Imported from TMDB', $title);

    Notification::assertSentTo(
        $user,
        RequestResolved::class,
        function (RequestResolved $notification) use ($request, $title) {
            $mail = $notification->toMail($request->user);

            return $mail->subject === 'Your title request for "Dune Part Two" has been resolved'
                && str_contains($mail->introLines[0], 'has been added to our catalog')
                && $mail->actionUrl === url('/titles/'.$title->slug);
        }
    );
});
