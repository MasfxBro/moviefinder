<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClipLink;
use App\Models\LinkReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LinkReportFactory extends Factory
{
    protected $model = LinkReport::class;

    public function definition(): array
    {
        return [
            'clip_link_id' => ClipLink::factory(),
            'user_id' => User::factory(),
            'reason' => fake()->randomElement(['dead', 'wrong_scene', 'copyright', 'inappropriate']),
            'details' => fake()->optional()->sentence(),
            'status' => 'open',
        ];
    }
}
