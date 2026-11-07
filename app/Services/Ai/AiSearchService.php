<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\ClipFinder;
use App\Contracts\LlmClient;
use App\Contracts\MetadataProvider;
use App\Contracts\PlanResolver;
use App\DTOs\CandidateData;
use App\DTOs\SceneQuery;
use App\Enums\MediaType;
use App\Enums\Plan;
use App\Exceptions\Tmdb\TmdbException;
use App\Models\Title;
use App\Models\User;
use App\Services\Catalog\TitleImporter;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AiSearchService
{
    public function __construct(
        private readonly PlanResolver $plansResolver,
        private readonly PlanCatalog $plans,
        private readonly FreePlaceService $places,
        private readonly QuotaService $quota,
        private readonly AiBudgetService $budget,
        private readonly AiAccessState $accessState,
        private readonly AnswerCache $answers,
        private readonly ConversationStore $conversations,
        private readonly ModelRouter $router,
        private readonly PromptBuilder $prompts,
        private readonly ReplyValidator $validator,
        private readonly LlmClient $llm,
        private readonly MetadataProvider $tmdb,
        private readonly TitleImporter $importer,
        private readonly CandidateCritic $critic,
        private readonly AiCostCalculator $costs,
        private readonly \App\Services\Ai\ClipFinder $clipFinder,
    ) {}

    /** @return array<string, mixed> */
    public function search(User $user, string $text, ?string $conversationId = null, string $mode = 'global', ?int $titleId = null): array
    {
        $text = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $text) ?? '');
        if ($text === '' || mb_strlen($text) > 600) {
            throw new InvalidArgumentException('Message must contain 1 to 600 characters.');
        }

        $titleContext = null;
        if ($mode === 'title') {
            $title = Title::query()->whereKey($titleId)->where('adult', false)->where('is_hidden', false)->firstOrFail();
            $titleContext = ['title' => $title->title, 'year' => $title->release_year, 'overview' => $title->overview ?? ''];
        }

        $plan = $this->plansResolver->resolve($user);
        $now = CarbonImmutable::now(config('app.business_timezone', 'Asia/Jakarta'));
        if ($plan === Plan::Free && ! $this->places->holds($user, $now) && ! $this->places->take($user, $now)) {
            throw new RuntimeException('Free AI places are full.');
        }
        $windowStart = $now->startOfMonth();
        $windowEnd = $now->endOfMonth();
        $budget = $this->budget->check($user, $plan, $windowStart, $windowEnd);
        if (! $budget['allowed']) {
            throw new RuntimeException('AI allowance used.');
        }

        $conversation = $this->conversations->load($user, $conversationId);
        $smart = $plan === Plan::Pro && $budget['smart'];
        $reservation = $this->quota->reserve($user, smart: $smart);
        $cached = $mode === 'global' && $plan === Plan::Free ? $this->answers->get($text) : null;
        if ($cached !== null) {
            $this->quota->commit($user, $reservation, ['mode' => 'global', 'model_tier' => 'standard', 'cost_idr' => 0]);

            return [...$cached, 'conversation_id' => $conversation['conversation_id'], 'quota' => $this->accessState->for($user)];
        }

        $route = $this->router->route($user, $smart);
        if ($plan === Plan::Free && $route['key'] !== (string) config('services.gemini.free_key', '')) {
            $this->quota->release($user, $reservation, smart: $smart);
            throw new RuntimeException('Free plan key selection is invalid.');
        }
        $cost = 0;
        $inputTokens = 0;
        $outputTokens = 0;

        try {
            $messages = $this->prompts->build($text, $conversation['history'], $mode, $titleContext);
            $reply = $this->validator->validateWithRepair(
                json_encode($this->llm->structured($messages, $this->validator->schema(), $route['plan'], $route['model']), JSON_THROW_ON_ERROR),
                fn (string $invalid): array => $this->llm->structured([...$messages, ['role' => 'user', 'parts' => [['text' => 'Repair invalid JSON to match the required schema: '.$invalid]]]], $this->validator->schema(), $route['plan'], $route['model']),
                mode: $mode,
                plan: $plan->value,
            );

            if ($reply->intent === 'refuse') {
                $this->quota->release($user, $reservation, smart: $smart);

                $message = $mode === 'title'
                    ? 'I cannot help with that request.'
                    : 'This assistant can only help with finding movies and series, and it cannot help with that request.';

                return ['reply' => $message, 'titles' => [], 'clips' => [], 'fallback_links' => [], 'conversation_id' => $conversation['conversation_id'], 'quota' => $this->accessState->for($user)];
            }

            if ($mode === 'title') {
                $clips = [];
                $fallbackLinks = [];
                $clipNotice = null;
                $searchSuggestions = null;
                if ($reply->intent === 'find_scene' && $titleContext !== null) {
                    $sceneQuery = new SceneQuery(sha1(json_encode($reply->scene, JSON_THROW_ON_ERROR)), $reply->scene['description'] ?? '', $reply->scene['keywords'] ?? []);
                    if ($this->clipFinder instanceof ClipFinder) {
                        $clipResults = $this->clipFinder->find($title, $sceneQuery, $plan, $user);
                        $clips = $clipResults->clips;
                        $fallbackLinks = array_values(array_filter($clips, fn (mixed $clip): bool => is_array($clip) && ($clip['verified'] ?? true) === false));
                        $clipNotice = $clipResults->notice;
                        $searchSuggestions = $clipResults->searchSuggestions;
                    } else {
                        ['clips' => $clips, 'fallback_links' => $fallbackLinks] = $this->clipFinder->find($title, $reply->scene);
                    }
                }
                $message = $reply->reply;
                $cost = $this->costs->calculate($inputTokens, $outputTokens, $route['tier']);
                $this->conversations->save($user, $conversation['conversation_id'], [...$conversation['history'], ['role' => 'user', 'content' => $text], ['role' => 'assistant', 'content' => $message]]);
                $this->quota->commit($user, $reservation, ['mode' => 'title', 'model' => $route['model'], 'model_tier' => $route['tier'], 'input_tokens' => $inputTokens, 'output_tokens' => $outputTokens, 'cost_idr' => $cost]);
                $this->budget->addCost($user, $plan, $windowStart, $windowEnd, $cost);

                return ['reply' => $message, 'titles' => [], 'clips' => $clips, 'fallback_links' => $fallbackLinks, 'clip_notice' => $clipNotice, 'search_suggestions' => $searchSuggestions, 'conversation_id' => $conversation['conversation_id'], 'quota' => $this->accessState->for($user), 'intent' => $reply->intent];
            }

            $verified = [];
            foreach ($reply->candidates as $candidate) {
                $title = $this->verify($candidate);
                if ($title !== null) {
                    $verified[] = ['candidate' => $candidate, 'title' => $title];
                }
            }
            if ($smart) {
                $verified = $this->critic->reorder($verified, $text);
            }
            $verified = array_slice($verified, 0, (int) $this->plans->get($plan)['title_cards']);
            $titles = array_map(fn (array $item): array => [
                'id' => $item['title']->id,
                'title' => $item['title']->title,
                'year' => $item['title']->release_year,
                'overview' => $item['title']->overview,
                'directors' => $item['title']->people->whereIn('role', ['director', 'creator'])->pluck('name')->values()->all(),
                'reason' => $item['candidate']->reason,
                'confidence' => $item['candidate']->confidence,
                'url' => route('titles.show', $item['title']->slug),
            ], $verified);
            $message = $reply->reply;
            if ($titles !== [] && max(array_column($titles, 'confidence')) < 0.6) {
                $message = 'No confident match was found. Here are the closest ones; please add more details if you can.';
            } elseif ($titles === [] && $reply->candidates !== []) {
                $message = 'No verifiable match was found. Please add more details if you can.';
            }

            $cost = $this->costs->calculate($inputTokens, $outputTokens, $route['tier']);
            $history = [...$conversation['history'], ['role' => 'user', 'content' => $text], ['role' => 'assistant', 'content' => $message]];
            $this->conversations->save($user, $conversation['conversation_id'], $history);
            $this->quota->commit($user, $reservation, ['mode' => 'global', 'model' => $route['model'], 'model_tier' => $route['tier'], 'input_tokens' => $inputTokens, 'output_tokens' => $outputTokens, 'cost_idr' => $cost]);
            $this->budget->addCost($user, $plan, $windowStart, $windowEnd, $cost);
            $result = ['reply' => $message, 'titles' => $titles, 'clips' => [], 'fallback_links' => [], 'conversation_id' => $conversation['conversation_id'], 'quota' => $this->accessState->for($user)];
            if ($plan === Plan::Free) {
                $this->answers->put($text, $result);
            }

            return $result;
        } catch (Throwable $exception) {
            $this->quota->release($user, $reservation, smart: $smart);
            throw $exception;
        }
    }

    private function verify(CandidateData $candidate): ?Title
    {
        $types = $candidate->mediaType === 'movie' ? [MediaType::Movie] : ($candidate->mediaType === 'tv' ? [MediaType::Tv] : [MediaType::Movie, MediaType::Tv]);
        foreach ($types as $type) {
            try {
                foreach ($this->tmdb->search($candidate->title, $type) as $match) {
                    $similarity = 0.0;
                    similar_text(mb_strtolower($candidate->title), mb_strtolower($match->title), $similarity);
                    if ($similarity < 65 || ($candidate->year !== null && $match->releaseYear !== null && abs($candidate->year - $match->releaseYear) > 1)) {
                        continue;
                    }
                    $title = Title::query()->where('tmdb_id', $match->tmdbId)->where('media_type', $type->value)->first();

                    return $title ?? $this->importer->import($match->tmdbId, $type, bypassThrottle: true);
                }
            } catch (TmdbException|InvalidArgumentException) {
                continue;
            }
        }

        return null;
    }
}
