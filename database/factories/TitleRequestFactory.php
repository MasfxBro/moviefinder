<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MediaType;
use App\Enums\RequestStatus;
use App\Models\TitleRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TitleRequest>
 */
class TitleRequestFactory extends Factory
{
    protected $model = TitleRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'media_type' => fake()->randomElement([MediaType::Movie, MediaType::Tv]),
            'title_text' => fake()->words(3, true),
            'release_year' => fake()->numberBetween(1980, 2026),
            'note' => fake()->optional()->sentence(),
            'status' => RequestStatus::Pending,
            'admin_note' => null,
            'resolved_title_id' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => RequestStatus::Approved,
        ]);
    }

    public function rejected(?string $reason = 'Not found'): static
    {
        return $this->state(fn () => [
            'status' => RequestStatus::Rejected,
            'admin_note' => $reason,
        ]);
    }

    public function added(): static
    {
        return $this->state(fn () => [
            'status' => RequestStatus::Added,
        ]);
    }
}
