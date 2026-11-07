<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\ClipLink;

/** @param array<ClipLink|array<string, mixed>> $clips */
readonly class ClipResultSet
{
    public function __construct(public array $clips, public bool $verified, public mixed $searchSuggestions = null, public ?string $notice = null) {}
}
