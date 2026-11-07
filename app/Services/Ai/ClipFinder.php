<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Title;

class ClipFinder
{
    /** @return array{clips: array<int, array<string, mixed>>, fallback_links: array<int, array<string, mixed>>} */
    public function find(Title $title, array $scene): array
    {
        return ['clips' => [], 'fallback_links' => []];
    }
}
