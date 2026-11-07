<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChannelAllowlist;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['channel_name' => 'Placeholder Official Channel', 'trust' => 'official', 'notes' => 'Replace with a verified official channel.'],
            ['channel_name' => 'Placeholder Trusted Channel', 'trust' => 'trusted', 'notes' => 'Replace with a reviewed trusted channel.'],
        ] as $channel) {
            ChannelAllowlist::query()->firstOrCreate(
                ['platform' => 'youtube', 'channel_id' => null, 'channel_name' => $channel['channel_name']],
                ['trust' => $channel['trust'], 'notes' => $channel['notes']],
            );
        }
    }
}
