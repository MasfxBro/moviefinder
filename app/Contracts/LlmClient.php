<?php

declare(strict_types=1);

namespace App\Contracts;

interface LlmClient
{
    /** @param array<int, array{role: string, parts: array<int, array<string, mixed>>}> $messages @param array<string, mixed> $schema @return array<string, mixed> */
    public function structured(array $messages, array $schema, string $plan = 'free', string $model = ''): array;
}
