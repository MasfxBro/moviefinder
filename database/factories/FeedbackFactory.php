<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['bug', 'suggestion', 'content', 'praise', 'other']),
            'message' => fake()->paragraph(),
            'rating' => fake()->optional()->numberBetween(1, 5),
            'page_path' => fake()->optional()->randomElement(['/browse', '/search', '/pricing']),
            'status' => 'new',
            'admin_note' => null,
            'handled_by' => null,
        ];
    }
}
