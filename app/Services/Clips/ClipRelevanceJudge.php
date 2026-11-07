<?php

declare(strict_types=1);

namespace App\Services\Clips;

use App\Contracts\LlmClient;
use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Models\Title;

class ClipRelevanceJudge
{
    public function __construct(private readonly LlmClient $llm) {}

    /** @param array<int, array<string, mixed>> $candidates @return array<int, array<string, mixed>> */
    public function judge(Title $title, SceneQuery $scene, Plan $plan, array $candidates): array
    {
        $limit = (int) config('plans.'.$plan->value.'.clip_candidates', 0);
        $candidates = array_slice($candidates, 0, $limit);
        if ($candidates === []) {
            return [];
        }

        $response = $this->llm->structured([
            ['role' => 'user', 'parts' => [['text' => json_encode([
                'task' => 'Score each clip from 0 to 1 for whether it depicts the requested scene. Return only JSON scores, in candidate order.',
                'title' => $title->title,
                'scene' => $scene->description,
                'keywords' => $scene->keywords,
                'candidates' => array_map(fn (array $candidate): array => array_intersect_key($candidate, array_flip(['title', 'channel_name', 'snippet'])), $candidates),
            ], JSON_THROW_ON_ERROR)]]],
        ], ['type' => 'OBJECT', 'properties' => ['scores' => ['type' => 'ARRAY', 'items' => ['type' => 'NUMBER']]], 'required' => ['scores']], 'plus', (string) config('ai.models.standard', ''));

        $scores = $response['scores'] ?? [];
        foreach ($candidates as $index => &$candidate) {
            $score = $scores[$index] ?? null;
            $candidate['score'] = is_numeric($score) ? max(0, min(1, (float) $score)) : 0;
        }
        unset($candidate);

        return array_values(array_filter($candidates, fn (array $candidate): bool => $candidate['score'] >= 0.6));
    }
}
