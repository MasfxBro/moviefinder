<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class AiReply
{
    /** @param array<CandidateData> $candidates @param array{description: ?string, keywords: array<string>, approx_position: ?string} $scene */
    public function __construct(
        public string $intent,
        public ?string $refusalReason,
        public string $reply,
        public ?string $clarifyingQuestion,
        public array $candidates,
        public array $scene,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'intent' => $this->intent,
            'refusal_reason' => $this->refusalReason,
            'reply' => $this->reply,
            'clarifying_question' => $this->clarifyingQuestion,
            'candidates' => array_map(fn (CandidateData $candidate): array => [
                'title' => $candidate->title,
                'year' => $candidate->year,
                'media_type' => $candidate->mediaType,
                'confidence' => $candidate->confidence,
                'reason' => $candidate->reason,
            ], $this->candidates),
            'scene' => $this->scene,
        ];
    }
}
