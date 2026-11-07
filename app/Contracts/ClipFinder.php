<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ClipResultSet;
use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Models\Title;
use App\Models\User;

interface ClipFinder
{
    public function find(Title $title, SceneQuery $scene, Plan $plan, ?User $user = null): ClipResultSet;
}
