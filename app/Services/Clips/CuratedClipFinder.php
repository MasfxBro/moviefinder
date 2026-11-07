<?php

declare(strict_types=1);

namespace App\Services\Clips;

use App\Contracts\ClipFinder;
use App\DTOs\ClipResultSet;
use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Models\ClipLink;
use App\Models\Title;
use App\Models\User;

class CuratedClipFinder implements ClipFinder
{
    public function find(Title $title, SceneQuery $scene, Plan $plan, ?User $user = null): ClipResultSet
    {
        $clips = ClipLink::query()->where('title_id', $title->id)->where('source', 'admin')->where('status', 'active')->get()->all();

        return new ClipResultSet($clips, $clips !== []);
    }
}
