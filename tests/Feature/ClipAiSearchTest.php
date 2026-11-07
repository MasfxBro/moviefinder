<?php

declare(strict_types=1);

use App\Contracts\GroundedLlmClient;
use App\Contracts\LlmClient;
use App\Contracts\PlanResolver;
use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Models\Title;
use App\Models\User;
use App\Services\Ai\AiBudgetService;
use App\Services\Ai\PlanCatalog;
use App\Services\Clips\AiSearchClipFinder;
use App\Services\Clips\ChannelPolicy;
use App\Services\Clips\ClipRelevanceJudge;
use App\Services\Clips\OembedValidator;
use App\Services\Clips\YoutubeOembed;
use App\Services\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function groundedClipFinder(array $answer, bool $budgetAllowed = true): AiSearchClipFinder
{
    $llm = new class($answer) implements GroundedLlmClient
    {
        public function __construct(private array $answer) {}

        public function grounded(array $messages, string $model): array
        {
            return $this->answer;
        }
    };
    $budget = Mockery::mock(AiBudgetService::class);
    $budget->shouldReceive('check')->andReturn(['allowed' => $budgetAllowed, 'clip_search' => $budgetAllowed]);
    $budget->shouldReceive('reserveClipSearch')->andReturn($budgetAllowed);
    $resolver = new class implements PlanResolver
    {
        public function resolve(User $user): Plan
        {
            return Plan::Plus;
        }
    };

    return new AiSearchClipFinder($llm, app(ClipRelevanceJudge::class), $budget, app(PlanCatalog::class), app(Settings::class), app(YoutubeOembed::class), app(OembedValidator::class), app(ChannelPolicy::class), $resolver);
}

test('grounded results exclude model-typed URLs, reject unsafe redirects, and fall back when budgets are exhausted', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://google.com/*' => Http::response('', 302, ['Location' => 'https://evil.example/watch?v=AbCdEf123_-']),
        'https://www.youtube.com/oembed*' => Http::response(['title' => 'Video', 'author_name' => 'Channel']),
    ]);
    app()->instance(LlmClient::class, new class implements LlmClient
    {
        public function structured(array $messages, array $schema, string $plan = 'free', string $model = ''): array
        {
            return ['scores' => array_fill(0, 5, 0.9)];
        }
    });
    config(['services.gemini.paid_key' => 'paid-key', 'clips.grounded_daily_budget' => 10]);
    Cache::flush();
    $title = Title::factory()->create();
    $scene = new SceneQuery('scene-test', 'final confrontation', ['finale']);
    $payload = ['candidates' => [['content' => ['parts' => [['text' => 'Try https://www.youtube.com/watch?v=AbCdEf123_-']]], 'groundingMetadata' => ['groundingChunks' => [['web' => ['uri' => 'https://google.com/redirect?id=1', 'title' => 'Grounded video']]], 'searchEntryPoint' => ['renderedContent' => '<div>exact</div>']]]]];
    $finder = groundedClipFinder($payload);
    $result = $finder->find($title, $scene, Plan::Plus, new User(['id' => 1]));
    expect($result->clips)->toHaveCount(1)->and($result->clips[0]['url'])->toContain('youtube.com/results?search_query=');

    $exhausted = groundedClipFinder($payload, false)->find($title, $scene, Plan::Plus, new User(['id' => 1]));
    expect($exhausted->clips[0]['url'])->toContain('youtube.com/results?search_query=');
});

test('relevance judge keeps score at or above 0.6 and rejects lower scores', function () {
    $title = new Title(['title' => 'Example']);
    $scene = new SceneQuery('scene-test', 'a duel');
    $client = new class implements LlmClient
    {
        public function structured(array $messages, array $schema, string $plan = 'free', string $model = ''): array
        {
            return ['scores' => [0.59, 0.6]];
        }
    };
    $judge = new ClipRelevanceJudge($client);
    $judged = $judge->judge($title, $scene, Plan::Plus, [['url' => 'one'], ['url' => 'two']]);
    expect($judged)->toHaveCount(1)->and($judged[0]['url'])->toBe('two');
});
