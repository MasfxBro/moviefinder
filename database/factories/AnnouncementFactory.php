<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => fake()->randomElement(['info', 'warning', 'maintenance']),
            'message' => fake()->sentence(),
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
            'starts_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(10),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
