<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class SceneQuery
{
    /** @param array<string> $keywords */
    public function __construct(public string $sceneKey, public string $description = '', public array $keywords = []) {}
}
