<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTOs\AiReply;
use App\DTOs\CandidateData;
use InvalidArgumentException;

class ReplyValidator
{
    /** @param callable(string): (array<string, mixed>|string) $repair */
    public function validateWithRepair(string $json, callable $repair, string $mode = 'global', string $plan = 'free'): AiReply
    {
        try {
            return $this->validate($json, $mode, $plan);
        } catch (InvalidArgumentException $exception) {
            try {
                return $this->validate($repair($json), $mode, $plan);
            } catch (InvalidArgumentException $repairException) {
                throw new InvalidArgumentException('AI reply remained invalid after one repair attempt.', previous: $repairException);
            }
        }
    }

    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'intent' => ['type' => 'STRING', 'enum' => ['find_title', 'find_scene', 'clarify', 'refuse', 'answer']],
                'refusal_reason' => ['type' => 'STRING', 'nullable' => true],
                'reply' => ['type' => 'STRING'],
                'clarifying_question' => ['type' => 'STRING', 'nullable' => true],
                'candidates' => ['type' => 'ARRAY', 'items' => ['type' => 'OBJECT']],
                'scene' => ['type' => 'OBJECT'],
            ],
            'required' => ['intent', 'reply'],
        ];
    }

    /** @param array<string, mixed>|string $reply */
    public function validate(array|string $reply, string $mode = 'global', string $plan = 'free'): AiReply
    {
        if (is_string($reply)) {
            try {
                $reply = json_decode($reply, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new InvalidArgumentException('Invalid AI JSON.', previous: $exception);
            }
        }

        if (! is_array($reply) || ! in_array($reply['intent'] ?? null, ['find_title', 'find_scene', 'clarify', 'refuse', 'answer'], true)) {
            throw new InvalidArgumentException('Invalid AI reply schema.');
        }

        $intent = $reply['intent'];
        if ($intent === 'answer' && $mode !== 'title') {
            throw new InvalidArgumentException('Answer intent is only valid in title mode.');
        }

        $candidateLimit = strtolower($plan) === 'free' ? config('ai.free_candidate_max', 3) : config('ai.candidate_max', 5);
        $candidates = [];
        foreach (array_slice(is_array($reply['candidates'] ?? null) ? $reply['candidates'] : [], 0, $candidateLimit) as $candidate) {
            if (! is_array($candidate) || ! is_string($candidate['title'] ?? null) || ! is_numeric($candidate['confidence'] ?? null)) {
                throw new InvalidArgumentException('Invalid candidate schema.');
            }

            $candidates[] = new CandidateData(
                $this->cleanText($candidate['title'], 200),
                isset($candidate['year']) && is_numeric($candidate['year']) ? (int) $candidate['year'] : null,
                in_array($candidate['media_type'] ?? '', ['movie', 'tv', 'unknown'], true) ? $candidate['media_type'] : 'unknown',
                max(0, min(1, (float) $candidate['confidence'])),
                $this->cleanText($candidate['reason'] ?? '', 200),
            );
        }

        $scene = is_array($reply['scene'] ?? null) ? $reply['scene'] : [];
        $keywords = array_values(array_filter($scene['keywords'] ?? [], 'is_string'));
        $titleMode = $mode === 'title';

        return new AiReply(
            $intent,
            in_array($reply['refusal_reason'] ?? null, ['off_topic', 'adult', 'piracy', 'unsafe'], true) ? $reply['refusal_reason'] : null,
            $this->cleanText($reply['reply'] ?? '', (int) config($titleMode ? 'ai.title_reply_max' : 'ai.reply_max', $titleMode ? 600 : 300)),
            isset($reply['clarifying_question']) && is_string($reply['clarifying_question']) ? $this->cleanText($reply['clarifying_question'], 200) : null,
            $titleMode ? [] : $candidates,
            [
                'description' => isset($scene['description']) && is_string($scene['description']) ? $this->cleanText($scene['description'], 300) : null,
                'keywords' => array_map(fn (string $keyword): string => $this->cleanText($keyword, 50), array_slice($keywords, 0, 6)),
                'approx_position' => isset($scene['approx_position']) && is_string($scene['approx_position']) ? $this->cleanText($scene['approx_position'], 100) : null,
            ],
        );
    }

    private function cleanText(mixed $value, int $limit): string
    {
        $text = is_string($value) ? strip_tags($value) : '';
        $text = preg_replace('/https?:\/\/\S+|www\.\S+/i', '', $text) ?? $text;

        return mb_substr($text, 0, $limit);
    }
}
