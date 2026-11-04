<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TitleFactory extends Factory
{
    protected $model = Title::class;

    public function definition(): array
    {
        return [
            'tmdb_id' => fake()->unique()->numberBetween(1000, 999999),
            'source' => 'tmdb',
            'media_type' => fake()->randomElement(['movie', 'tv']),
            'title' => fake()->words(3, true),
            'original_title' => fake()->optional(0.3)->words(3, true),
            'release_year' => fake()->optional(0.9)->numberBetween(1970, 2026),
            'poster_path' => fake()->optional(0.8)->regexify('/[a-zA-Z0-9]{20}\.jpg'),
            'backdrop_path' => fake()->optional(0.6)->regexify('/[a-zA-Z0-9]{20}\.jpg'),
            'overview' => fake()->optional(0.7)->paragraph(),
            'tagline' => fake()->optional(0.3)->sentence(),
            'runtime_minutes' => fake()->optional(0.7)->numberBetween(60, 180),
            'seasons_count' => null,
            'episodes_count' => null,
            'original_language' => fake()->optional(0.9)->randomElement(['en', 'id', 'ja', 'ko', 'es', 'fr']),
            'vote_average' => fake()->randomFloat(1, 0, 10),
            'vote_count' => fake()->numberBetween(0, 50000),
            'popularity' => fake()->randomFloat(2, 0, 1000),
            'trailer_youtube_key' => fake()->optional(0.4)->regexify('[a-zA-Z0-9_-]{11}'),
            'adult' => false,
            'is_hidden' => false,
            'synced_at' => now(),
            'locked_fields' => null,
            'last_viewed_at' => null,
            'created_by' => null,
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'tmdb_id' => null,
            'source' => 'manual',
            'synced_at' => null,
            'created_by' => User::factory(),
        ]);
    }

    public function movie(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => 'movie',
            'seasons_count' => null,
            'episodes_count' => null,
        ]);
    }

    public function tv(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => 'tv',
            'runtime_minutes' => fake()->optional(0.5)->numberBetween(20, 60),
            'seasons_count' => fake()->numberBetween(1, 10),
            'episodes_count' => fake()->numberBetween(6, 200),
        ]);
    }
}
