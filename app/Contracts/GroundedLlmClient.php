<?php

declare(strict_types=1);

namespace App\Contracts;

interface GroundedLlmClient
{
    /** @param array<int, array{role: string, parts: array<int, array<string, mixed>>}> $messages @return array<string, mixed> */
    public function grounded(array $messages, string $model): array;
}
