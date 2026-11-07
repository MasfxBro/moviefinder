<?php

declare(strict_types=1);

namespace App\Services\Clips;

use App\Contracts\ClipFinder;
use App\DTOs\ClipResultSet;
use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Models\Title;
use Illuminate\Support\Facades\Cache;

class ClipFinderChain implements ClipFinder
{
    /** @param array<ClipFinder> $finders */
    public function __construct(private readonly array $finders) {}

    public function find(Title $title, SceneQuery $scene, Plan $plan): ClipResultSet
    {
        $key = 'clips:negative:'.$title->id.':'.sha1($scene->sceneKey);
        if (Cache::get($key) === true) {
            return $this->fallback($title, $scene, $plan);
        }

        foreach ($this->finders as $finder) {
            if ($finder instanceof SearchLinkFinder) {
                continue;
            }

            $results = $finder->find($title, $scene, $plan);
            if ($results->clips !== []) {
                Cache::forget($key);

                return $results;
            }
        }

        Cache::put($key, true, now()->addHours(24));

        return $this->fallback($title, $scene, $plan);
    }

    private function fallback(Title $title, SceneQuery $scene, Plan $plan): ClipResultSet
    {
        foreach ($this->finders as $finder) {
            if ($finder instanceof SearchLinkFinder) {
                return $finder->find($title, $scene, $plan);
            }
        }

        return (new SearchLinkFinder)->find($title, $scene, $plan);
    }
}
