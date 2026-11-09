<?php

declare(strict_types=1);

namespace App\Services\Clips;

use App\Contracts\ClipFinder;
use App\DTOs\ClipResultSet;
use App\DTOs\SceneQuery;
use App\Enums\Plan;
use App\Models\ChannelAllowlist;
use App\Models\ClipLink;
use App\Models\Title;
use App\Models\User;

class CuratedClipFinder implements ClipFinder
{
    public function find(Title $title, SceneQuery $scene, Plan $plan, ?User $user = null): ClipResultSet
    {
        $query = ClipLink::query()->where('title_id', $title->id)->where('source', 'admin')->where('status', 'active');
        $blockedChannelIds = ChannelAllowlist::where('trust', 'blocked')->whereNotNull('channel_id')->pluck('channel_id');
        $blockedChannelNames = ChannelAllowlist::where('trust', 'blocked')->pluck('channel_name')->map(fn ($n) => mb_strtolower((string) $n));

        if ($blockedChannelIds->isNotEmpty()) {
            $query->whereNotIn('channel_id', $blockedChannelIds);
        }
        foreach ($blockedChannelNames as $bName) {
            $query->whereRaw('LOWER(channel_name) != ?', [$bName]);
        }

        $clips = $query->get()->all();

        return new ClipResultSet($clips, $clips !== []);
    }
}
