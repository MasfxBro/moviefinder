<?php

declare(strict_types=1);

use App\Contracts\LlmClient;
use App\Contracts\MetadataProvider;
use App\Contracts\PlanResolver;
use App\Enums\Plan;
use App\Models\Title;
use App\Models\User;
use App\Services\Ai\AiBudgetService;
use App\Services\Ai\AiSearchService;
use App\Services\Ai\AnswerCache;
use App\Services\Ai\CandidateCritic;
use App\Services\Ai\ClipFinder;
use App\Services\Ai\ConversationStore;
use App\Services\Ai\FakeLlmClient;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\ModelRouter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

beforeEach(function (): void {
    config([
        'app.business_timezone' => 'Asia/Jakarta',
        'ai.global_daily_cap' => 1000,
        'ai.free.pool_daily_limit' => 100,
        'ai.free.overbook_pct' => 100,
        'ai.free.waves' => [['time' => '00:00', 'share' => 100]],
        'plans.free.daily_units' => 5,
        'plans.free.title_cards' => 3,
        'plans.free.conversation_turns' => 8,
        'plans.free.ai_rate_limit_per_minute' => 6,
        'plans.plus.daily_units' => 10,
        'plans.plus.title_cards' => 4,
        'plans.plus.conversation_turns' => 12,
        'plans.plus.ai_rate_limit_per_minute' => 8,
        'plans.pro.daily_units' => 12,
        'plans.pro.smart_searches_daily' => 4,
        'plans.pro.title_cards' => 5,
        'plans.pro.conversation_turns' => 16,
        'plans.pro.ai_rate_limit_per_minute' => 12,
    ]);
    Cache::flush();
    Http::preventStrayRequests();
});

function aiUserPlan(Plan $plan): User
{
    $user = User::factory()->create();
    $resolver = Mockery::mock(PlanResolver::class);
    $resolver->shouldReceive('resolve')->andReturn($plan);
    app()->instance(PlanResolver::class, $resolver);

    return $user;
}

function prepareFreePlace(User $user): void
{
    $today = CarbonImmutable::now('Asia/Jakarta')->toDateString();
    DB::table('ai_free_pool')->insertOrIgnore(['used_on' => $today, 'taken' => 0]);
    DB::table('ai_free_places')->insertOrIgnore(['used_on' => $today, 'user_id' => $user->id, 'taken_at' => now()]);
}

function aiReply(array $candidates = [], string $intent = 'find_title'): array
{
    return [
        'intent' => $intent,
        'refusal_reason' => $intent === 'refuse' ? 'off_topic' : null,
        'reply' => $intent === 'refuse' ? 'Refused.' : 'Here are possible matches.',
        'candidates' => $candidates,
        'scene' => ['description' => null, 'keywords' => [], 'approx_position' => null],
    ];
}

function aiSearch(User $user): AiSearchService
{
    return app(AiSearchService::class);
}

it('keeps conversations isolated, slides expiry, and enforces plan turn limit', function () {
    $first = aiUserPlan(Plan::Free);
    $second = User::factory()->create();
    $store = app(ConversationStore::class);
    $conversation = $store->load($first, null);
    $store->save($first, $conversation['conversation_id'], array_fill(0, 10, ['role' => 'user', 'content' => 'clue']));

    expect($store->load($first, $conversation['conversation_id'])['history'])->toHaveCount(8)
        ->and(fn () => $store->load($second, $conversation['conversation_id']))->toThrow(AccessDeniedHttpException::class);
});

it('never caches personal data and normalizes questions', function () {
    $cache = new AnswerCache;
    $cache->put('  Find   a movie ', ['reply' => 'cached']);
    $cache->put('alice@example.com identify this', ['reply' => 'personal']);
    $cache->put('My number is +1 415 555 0123', ['reply' => 'personal']);
    $cache->put('Nama saya Sari, cari film', ['reply' => 'personal']);

    expect($cache->get('find a movie')['reply'])->toBe('cached')
        ->and($cache->get('alice@example.com identify this'))->toBeNull()
        ->and($cache->get('My number is +1 415 555 0123'))->toBeNull()
        ->and($cache->get('Nama saya Sari, cari film'))->toBeNull();
});

it('routes models and API keys by plan', function () {
    config(['ai.models.standard' => 'standard-model', 'ai.models.smart' => 'smart-model', 'services.gemini.free_key' => 'free-key', 'services.gemini.paid_key' => 'paid-key']);
    $router = app(ModelRouter::class);
    $free = aiUserPlan(Plan::Free);
    expect($router->route($free, true))->toMatchArray(['model' => 'standard-model', 'tier' => 'standard', 'key' => 'free-key']);
    $pro = aiUserPlan(Plan::Pro);
    $proResolver = Mockery::mock(PlanResolver::class);
    $proResolver->shouldReceive('resolve')->andReturn(Plan::Pro);
    app()->instance(PlanResolver::class, $proResolver);
    $router = app(ModelRouter::class);
    expect($router->route($pro, true))->toMatchArray(['model' => 'smart-model', 'tier' => 'smart', 'key' => 'paid-key'])
        ->and($router->route($pro, false)['model'])->toBe('standard-model');
});

it('uses database title context, ignores client context and candidates, answers off-topic questions', function () {
    $user = aiUserPlan(Plan::Free);
    prepareFreePlace($user);
    $title = Title::factory()->create(['title' => 'Database Title', 'release_year' => 2001, 'overview' => 'Database overview']);
    $llm = Mockery::mock(LlmClient::class);
    $llm->shouldReceive('structured')->once()->withArgs(function (array $messages): bool {
        $prompt = $messages[0]['parts'][0]['text'];

        return str_contains($prompt, 'title="Database Title"')
            && str_contains($prompt, 'year=2001')
            && str_contains($prompt, 'overview="Database overview"')
            && ! str_contains($prompt, 'Forged context');
    })->andReturn(aiReply([['title' => 'Forged Candidate', 'confidence' => 0.99]], 'answer'));
    app()->instance(LlmClient::class, $llm);
    $metadata = Mockery::mock(MetadataProvider::class);
    $metadata->shouldNotReceive('search');
    app()->instance(MetadataProvider::class, $metadata);

    $result = aiSearch($user)->search($user, 'What is the capital of France?', mode: 'title', titleId: $title->id);

    expect($result['reply'])->toBe('Here are possible matches.')
        ->and($result['titles'])->toBeEmpty()
        ->and(DB::table('ai_usage_log')->where('user_id', $user->id)->value('mode'))->toBe('title');
});

it('runs ClipFinder for title scene requests and retains safety refusals', function () {
    $user = aiUserPlan(Plan::Free);
    prepareFreePlace($user);
    $title = Title::factory()->create();
    $finder = Mockery::mock(ClipFinder::class);
    $finder->shouldReceive('find')->once()->andReturn(['clips' => [], 'fallback_links' => []]);
    app()->instance(ClipFinder::class, $finder);
    app()->instance(LlmClient::class, new class implements LlmClient
    {
        public function structured(array $messages, array $schema, string $plan = 'free', string $model = ''): array
        {
            return aiReply(intent: 'find_scene');
        }
    });

    aiSearch($user)->search($user, 'Which scene?', mode: 'title', titleId: $title->id);

    $unsafe = aiUserPlan(Plan::Plus);
    $refuser = Mockery::mock(LlmClient::class);
    $refuser->shouldReceive('structured')->times(3)->andReturn(aiReply(intent: 'refuse'));
    app()->instance(LlmClient::class, $refuser);
    foreach (['explicit sexual content', 'pirated download', 'instructions for harm'] as $request) {
        expect(aiSearch($unsafe)->search($unsafe, $request, mode: 'title', titleId: $title->id)['titles'])->toBeEmpty();
    }
});

it('drops candidates not verified by TMDB', function () {
    $user = aiUserPlan(Plan::Free);
    prepareFreePlace($user);
    $llm = Mockery::mock(LlmClient::class);
    $llm->shouldReceive('structured')->once()->andReturn(aiReply([['title' => 'Imaginary Film', 'year' => 2000, 'media_type' => 'movie', 'confidence' => 0.9, 'reason' => 'clue']]));
    app()->instance(LlmClient::class, $llm);
    $metadata = Mockery::mock(MetadataProvider::class);
    $metadata->shouldReceive('search')->once()->andReturn([]);
    app()->instance(MetadataProvider::class, $metadata);

    expect(aiSearch($user)->search($user, 'find imaginary film')['titles'])->toBeEmpty();
});

it('releases quota when the provider fails', function () {
    $user = aiUserPlan(Plan::Free);
    prepareFreePlace($user);
    $llm = Mockery::mock(LlmClient::class);
    $llm->shouldReceive('structured')->once()->andThrow(new RuntimeException('Provider unavailable'));
    app()->instance(LlmClient::class, $llm);

    expect(fn () => aiSearch($user)->search($user, 'find a western'))->toThrow(RuntimeException::class);
    expect((int) DB::table('ai_usage')->where('user_id', $user->id)->value('count'))->toBe(0)
        ->and(DB::table('ai_usage_log')->where('user_id', $user->id)->count())->toBe(0);
});

it('does not consume quota for refusals', function () {
    $user = aiUserPlan(Plan::Free);
    prepareFreePlace($user);
    $llm = Mockery::mock(LlmClient::class);
    $llm->shouldReceive('structured')->once()->andReturn(aiReply(intent: 'refuse'));
    app()->instance(LlmClient::class, $llm);

    expect(aiSearch($user)->search($user, 'piracy request')['titles'])->toBeEmpty()
        ->and((int) DB::table('ai_usage')->where('user_id', $user->id)->value('count'))->toBe(0);
});

it('does not let Free requests reach the paid API key', function () {
    config(['services.gemini.free_key' => 'free-key', 'services.gemini.paid_key' => 'paid-key', 'ai.models.standard' => 'standard-model']);
    $user = aiUserPlan(Plan::Free);
    prepareFreePlace($user);
    $llm = app(GeminiClient::class);
    app()->instance(LlmClient::class, $llm);
    Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"intent":"clarify","reply":"More clues?"}']]]]]])]);

    aiSearch($user)->search($user, 'find a western');
    Http::assertSent(fn (Request $request): bool => $request->header('x-goog-api-key') === ['free-key']);
    Http::assertNotSent(fn (Request $request): bool => $request->header('x-goog-api-key') === ['paid-key']);
});

it('calls CandidateCritic only for Pro Smart searches', function () {
    $plus = aiUserPlan(Plan::Plus);
    $critic = Mockery::mock(CandidateCritic::class);
    $critic->shouldNotReceive('reorder');
    app()->instance(CandidateCritic::class, $critic);
    app()->instance(LlmClient::class, new class implements LlmClient
    {
        public function structured(array $messages, array $schema, string $plan = 'free', string $model = ''): array
        {
            return aiReply();
        }
    });
    aiSearch($plus)->search($plus, 'find a movie');

    $pro = aiUserPlan(Plan::Pro);
    $smartCritic = Mockery::mock(CandidateCritic::class);
    $smartCritic->shouldReceive('reorder')->once()->with([], 'find a movie')->andReturn([]);
    app()->instance(CandidateCritic::class, $smartCritic);
    $metadata = Mockery::mock(MetadataProvider::class);
    $metadata->shouldReceive('search')->never();
    app()->instance(MetadataProvider::class, $metadata);

    aiSearch($pro)->search($pro, 'find a movie');
});

it('redirects guests and rate limits messages by each plan', function () {
    $this->post(route('ai.messages'), ['message' => 'find a film', 'mode' => 'global'])->assertRedirect(route('login'));
    foreach ([[Plan::Free, 6], [Plan::Plus, 8], [Plan::Pro, 12]] as [$plan, $limit]) {
        $user = aiUserPlan($plan);
        $this->actingAs($user);
        for ($attempt = 0; $attempt < $limit; $attempt++) {
            $this->postJson(route('ai.messages'), ['message' => 'find a film', 'mode' => 'global'])->assertSuccessful();
        }
        $this->postJson(route('ai.messages'), ['message' => 'find a film', 'mode' => 'global'])->assertTooManyRequests();
    }
});

it('returns user-facing errors for exhausted places and budget allowance', function () {
    $user = aiUserPlan(Plan::Free);
    config(['ai.free.pool_daily_limit' => 0, 'ai.free.waves' => []]);
    $this->actingAs($user)->postJson(route('ai.messages'), ['message' => 'find a movie', 'mode' => 'global'])
        ->assertOk()->assertJsonPath('reply', "Today's free AI places are full. The next free places open at 00:00 WIB.");

    $plus = aiUserPlan(Plan::Plus);
    $this->app->instance(PlanResolver::class, tap(Mockery::mock(PlanResolver::class), fn ($resolver) => $resolver->shouldReceive('resolve')->andReturn(Plan::Plus)));
    config(['plans.plus.budget_idr_period' => 1]);
    $now = CarbonImmutable::now('Asia/Jakarta');
    app(AiBudgetService::class)->addCost($plus, Plan::Plus, $now->startOfMonth(), $now->endOfMonth(), 1);
    $this->actingAs($plus)->postJson(route('ai.messages'), ['message' => 'find a movie', 'mode' => 'global'])
        ->assertOk()->assertJsonPath('reply', "You have used this period's AI allowance. It renews on ".($now->endOfMonth()->format('d M Y')).'.');
});

it('runs the fake LLM without making Google requests', function () {
    config(['ai.driver' => 'fake']);
    $fake = app(FakeLlmClient::class);
    expect($fake->structured([['role' => 'user', 'parts' => [['text' => 'find a film']]]], []))->toHaveKey('intent');
    Http::assertNothingSent();
});
