<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClipLink;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClipLinkFactory extends Factory
{
    protected $model = ClipLink::class;

    public function definition(): array
    {
        $videoId = fake()->regexify('[a-zA-Z0-9_-]{11}');

        return [
            'title_id' => Title::factory(),
            'video_platform' => 'youtube',
            'video_id' => $videoId,
            'url' => 'https://www.youtube.com/watch?v='.$videoId,
            'channel_name' => fake()->company(),
            'channel_id' => 'UC'.fake()->regexify('[a-zA-Z0-9_-]{22}'),
            'label' => fake()->sentence(4),
            'trust' => 'official',
            'source' => 'admin',
            'scene_key' => fake()->sha1(),
            'status' => 'active',
            'verified_at' => now(),
        ];
    }

    public function trusted(): self
    {
        return $this->state(fn () => ['trust' => 'trusted']);
    }

    public function unverified(): self
    {
        return $this->state(fn () => ['trust' => 'unverified']);
    }

    public function reported(): self
    {
        return $this->state(fn () => ['status' => 'reported']);
    }

    public function external(): self
    {
        return $this->state(fn () => [
            'video_platform' => 'other',
            'video_id' => null,
            'url' => 'https://vimeo.com/'.fake()->numberBetween(100000, 999999),
            'trust' => 'unverified',
        ]);
    }
}
