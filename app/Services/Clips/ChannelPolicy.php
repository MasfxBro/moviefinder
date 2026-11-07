<?php

declare(strict_types=1);

namespace App\Services\Clips;

use App\Models\ChannelAllowlist;

class ChannelPolicy
{
    /** @return array{allowed: bool, embeddable: bool, trust: ?string} */
    public function assess(string $platform, ?string $channelId, ?string $channelName): array
    {
        if ($platform !== 'youtube' || ($channelId === null && $channelName === null)) {
            return ['allowed' => true, 'embeddable' => false, 'trust' => null];
        }

        $channel = ChannelAllowlist::query()->where('platform', 'youtube')->where(function ($query) use ($channelId, $channelName): void {
            if ($channelId !== null) {
                $query->where('channel_id', $channelId);
            }
            if ($channelName !== null) {
                $method = $channelId === null ? 'whereRaw' : 'orWhereRaw';
                $query->{$method}('LOWER(channel_name) = ?', [mb_strtolower($channelName)]);
            }
        })->first();

        if ($channel?->trust === 'blocked') {
            return ['allowed' => false, 'embeddable' => false, 'trust' => 'blocked'];
        }

        $trust = $channel?->trust;

        return ['allowed' => true, 'embeddable' => in_array($trust, ['official', 'trusted'], true), 'trust' => $trust];
    }
}
