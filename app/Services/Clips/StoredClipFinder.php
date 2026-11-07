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

class StoredClipFinder implements ClipFinder
{
    public function find(Title $title, SceneQuery $scene, Plan $plan, ?User $user = null): ClipResultSet
    {
        $clips = ClipLink::query()->where('title_id', $title->id)->where('source', 'resolver')->where('scene_key', $scene->sceneKey)
            ->where('status', 'active')->where('verified_at', '>=', now()->subDays(30))->get()->all();

        return new ClipResultSet($clips, $clips !== []);
    }
}
