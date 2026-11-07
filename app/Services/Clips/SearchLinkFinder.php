<?php

declare(strict_types=1);

namespace App\Services\Clips;

use App\Contracts\ClipFinder;
use App\DTOs\ClipResultSet;
use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Models\Title;

class SearchLinkFinder implements ClipFinder
{
    public function find(Title $title, SceneQuery $scene, Plan $plan): ClipResultSet
    {
        $query = trim($title->title.' '.($title->release_year ?? '').' '.implode(' ', $scene->keywords).' scene');

        return new ClipResultSet([[
            'url' => 'https://www.youtube.com/results?search_query='.rawurlencode($query),
            'label' => 'Search YouTube',
            'trust' => 'unverified',
            'verified' => false,
        ]], false);
    }
}
