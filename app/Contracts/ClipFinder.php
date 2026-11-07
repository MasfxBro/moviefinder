<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ClipResultSet;
use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Models\Title;

interface ClipFinder
{
    public function find(Title $title, SceneQuery $scene, Plan $plan): ClipResultSet;
}
