<?php

declare(strict_types=1);

namespace App\Services\Clips;

use App\Contracts\ClipFinder;
use App\Contracts\GroundedLlmClient;
use App\Contracts\PlanResolver;
use App\DTOs\ClipResultSet;
use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Models\ClipLink;
use App\Models\Title;
use App\Models\User;
use App\Services\Ai\AiBudgetService;
use App\Services\Ai\PlanCatalog;
use App\Services\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class AiSearchClipFinder implements ClipFinder
{
    private string $notice = 'AI clip search opens with the public launch.';

    public function __construct(
        private readonly GroundedLlmClient $llm,
        private readonly ClipRelevanceJudge $judge,
        private readonly AiBudgetService $budget,
        private readonly PlanCatalog $plans,
        private readonly Settings $settings,
        private readonly YoutubeOembed $youtube,
        private readonly OembedValidator $oembed,
        private readonly ChannelPolicy $channels,
        private readonly PlanResolver $planResolver,
    ) {}

    public function find(Title $title, SceneQuery $scene, Plan $plan, ?User $user = null): ClipResultSet
    {
        $fallback = (new SearchLinkFinder)->find($title, $scene, $plan, $user);
        if ($user === null) {
            return $fallback;
        }
        $savedAndFallback = fn (): ClipResultSet => new ClipResultSet(array_slice([...$this->saved($title, $scene), ...$fallback->clips], 0, (int) $this->plans->get($plan)['clips_shown']), false);
        if ($plan === Plan::Free || $this->planResolver->resolve($user) !== $plan || ! $this->settings->get('features.ai_clip_search')) {
            return $savedAndFallback();
        }
        if (! is_string(config('services.gemini.paid_key')) || config('services.gemini.paid_key') === '') {
            return new ClipResultSet(array_slice([...$this->saved($title, $scene), ...$fallback->clips], 0, (int) $this->plans->get($plan)['clips_shown']), false, null, $this->notice);
        }

        $now = CarbonImmutable::now(config('app.business_timezone', 'Asia/Jakarta'));
        $budget = $this->budget->check($user, $plan, $now->startOfMonth(), $now->endOfMonth());
        if (! $budget['allowed'] || ! $budget['clip_search']) {
            return $savedAndFallback();
        }
        $dailyLimit = max(0, (int) $this->settings->get('clips.grounded_daily_budget'));
        $dailyKey = 'clips:grounded:'.$now->toDateString();
        if ($dailyLimit === 0 || Cache::get($dailyKey, 0) >= $dailyLimit) {
            return $savedAndFallback();
        }
        if (! $this->budget->reserveClipSearch($user, $plan, $now->startOfMonth(), $now->endOfMonth())) {
            return $savedAndFallback();
        }

        try {
            Cache::increment($dailyKey);
            $answer = $this->llm->grounded([['role' => 'user', 'parts' => [['text' => 'Find real videos (YouTube first) showing this scene. Give one-line reasons. Do not invent links. Title: '.$title->title.' ('.$title->release_year.'). Scene: '.$scene->description.'. Keywords: '.implode(', ', $scene->keywords)]]]], (string) config('ai.models.standard', ''));
            $metadata = $answer['candidates'][0]['groundingMetadata'] ?? [];
            $chunks = $metadata['groundingChunks'] ?? [];
            $urls = [];
            foreach ($chunks as $chunk) {
                $url = $chunk['web']['uri'] ?? null;
                if (is_string($url)) {
                    $urls[$url] = ['url' => $url, 'title' => $chunk['web']['title'] ?? '', 'snippet' => '', 'reason' => ''];
                }
            }
            $searchEntryPoint = $metadata['searchEntryPoint'] ?? null;
            $accepted = [];
            foreach ($urls as $candidate) {
                $url = PlatformUrl::parse($candidate['url']);
                if ($url === null) {
                    $resolved = $this->resolveOnce($candidate['url']);
                    $url = $resolved === null ? null : PlatformUrl::parse($resolved);
                }
                if ($url === null) {
                    continue;
                }
                $candidate['url'] = $url->canonicalUrl;
                $candidate['platform'] = $url->platform;
                $candidate['video_id'] = $url->videoId;
                $accepted[] = $candidate;
            }
            $judged = $this->judge->judge($title, $scene, $plan, $accepted);
            $clips = [];
            foreach ($judged as $candidate) {
                $validated = $candidate['platform'] === 'youtube' ? $this->youtube->check(PlatformUrl::parse($candidate['url'])) !== null : ($candidate['platform'] === 'external' || $this->oembed->check(PlatformUrl::parse($candidate['url'])));
                if (! $validated) {
                    continue;
                }
                $policy = $this->channels->assess($candidate['platform'], $candidate['channel_id'] ?? null, $candidate['channel_name'] ?? null);
                if (! $policy['allowed']) {
                    continue;
                }
                $link = ClipLink::query()->firstOrCreate(['title_id' => $title->id, 'url' => $candidate['url']], [
                    'video_platform' => $candidate['platform'] === 'youtube' ? 'youtube' : 'other', 'video_id' => $candidate['video_id'],
                    'channel_name' => $candidate['channel_name'] ?? null, 'channel_id' => $candidate['channel_id'] ?? null,
                    'label' => $candidate['reason'] ?: $candidate['title'], 'trust' => $policy['trust'] ?? 'unverified',
                    'source' => 'resolver', 'scene_key' => $scene->sceneKey, 'status' => 'active', 'verified_at' => now(),
                ]);
                $clips[] = $link;
            }

            if ($clips === []) {
                return new ClipResultSet(array_slice([...$this->saved($title, $scene), ...$fallback->clips], 0, (int) $this->plans->get($plan)['clips_shown']), false, $searchEntryPoint);
            }

            return new ClipResultSet(array_slice($clips, 0, (int) $this->plans->get($plan)['clips_shown']), true, $searchEntryPoint);
        } catch (Throwable) {
            return $savedAndFallback();
        }
    }

    /** @return array<int, ClipLink> */
    private function saved(Title $title, SceneQuery $scene): array
    {
        return ClipLink::query()->where('title_id', $title->id)->where('source', 'resolver')->where('scene_key', $scene->sceneKey)
            ->where('status', 'active')->where('verified_at', '>=', now()->subDays(30))->get()->all();
    }

    private function resolveOnce(string $url): ?string
    {
        try {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            if (! in_array($host, ['google.com', 'www.google.com', 'vertexaisearch.cloud.google.com'], true)) {
                return null;
            }
            $response = Http::withoutRedirecting()->connectTimeout(5)->timeout(5)->get($url);
            $location = $response->header('Location');
            if (! $response->redirect() || $location === '') {
                return null;
            }
            if (str_starts_with($location, '/')) {
                $host = parse_url($url, PHP_URL_HOST);
                $location = 'https://'.$host.$location;
            }

            return PlatformUrl::parse($location) === null ? null : $location;
        } catch (Throwable) {
            return null;
        }
    }
}
