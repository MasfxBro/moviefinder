<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\LlmClient;

class FakeLlmClient implements LlmClient
{
    public function structured(array $messages, array $schema, string $plan = 'free', string $model = ''): array
    {
        $text = mb_strtolower($messages[array_key_last($messages)]['parts'][0]['text'] ?? '');
        $reply = match (true) {
            str_contains($text, 'intent=refuse') || str_contains($text, 'refuse') || str_contains($text, 'pirat') || str_contains($text, 'download') || str_contains($text, 'explicit') || str_contains($text, 'harm') => ['intent' => 'refuse', 'refusal_reason' => 'piracy', 'reply' => 'I cannot help with pirated copies. I can help find legal ways to watch a title.'],
            str_contains($text, 'scene') || str_contains($text, 'adegan') => ['intent' => 'find_scene', 'refusal_reason' => null, 'reply' => 'I can help identify that scene. Do you remember who was present?', 'clarifying_question' => 'Who was present in the scene?', 'candidates' => [], 'scene' => ['description' => 'A character confronts another during a tense scene.', 'keywords' => ['confrontation', 'dialogue'], 'approx_position' => null]],
            str_contains($text, 'film') || str_contains($text, 'movie') || str_contains($text, 'series') || str_contains($text, 'judul') => ['intent' => 'find_title', 'refusal_reason' => null, 'reply' => 'These are plausible starting points; a real catalog check is needed.', 'clarifying_question' => null, 'candidates' => [['title' => 'The Shawshank Redemption', 'year' => 1994, 'media_type' => 'movie', 'confidence' => 0.42, 'reason' => 'A remembered prison drama could fit; more clues would help.']], 'scene' => ['description' => null, 'keywords' => [], 'approx_position' => null]],
            str_contains($text, 'title mode') || str_contains($text, 'answer') => ['intent' => 'answer', 'refusal_reason' => null, 'reply' => 'This example answer uses the selected title as context.', 'candidates' => [], 'scene' => ['description' => null, 'keywords' => [], 'approx_position' => null]],
            default => ['intent' => 'clarify', 'refusal_reason' => null, 'reply' => 'Could you share one more detail, such as the setting or approximate year?', 'clarifying_question' => 'What is one detail you remember?', 'candidates' => [], 'scene' => ['description' => null, 'keywords' => [], 'approx_position' => null]],
        };

        return $reply;
    }
}
