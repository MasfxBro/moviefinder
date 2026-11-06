<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class CandidateData
{
    public function __construct(
        public string $title,
        public ?int $year,
        public string $mediaType,
        public float $confidence,
        public string $reason,
    ) {}
}
