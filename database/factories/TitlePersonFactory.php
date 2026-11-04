<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Title;
use App\Models\TitlePerson;
use Illuminate\Database\Eloquent\Factories\Factory;

class TitlePersonFactory extends Factory
{
    protected $model = TitlePerson::class;

    public function definition(): array
    {
        return [
            'title_id' => Title::factory(),
            'name' => fake()->name(),
            'role' => fake()->randomElement(['director', 'creator', 'cast']),
            'position' => fake()->numberBetween(0, 10),
            'tmdb_person_id' => fake()->optional(0.7)->numberBetween(1000, 999999),
        ];
    }

    public function director(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'director',
            'position' => 0,
        ]);
    }

    public function cast(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'cast',
        ]);
    }
}
