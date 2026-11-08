<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OnboardingOutcome;
use App\Enums\Plan;
use App\Models\User;

class OnboardingService
{
    public function __construct(
        private readonly Settings $settings,
    ) {}

    public function shouldOffer(?User $user): bool
    {
        if ($user === null || ! $user->hasVerifiedEmail() || $user->isAdmin()) {
            return false;
        }

        if ($user->onboarding_seen_at !== null || $user->onboarding_outcome !== null) {
            return false;
        }

        return (bool) $this->settings->get('onboarding.enabled');
    }

    public function markSeen(User $user): void
    {
        $user->forceFill(['onboarding_seen_at' => now()])->save();
    }

    public function recordOutcome(User $user, OnboardingOutcome|string $outcome): void
    {
        $value = $outcome instanceof OnboardingOutcome ? $outcome : OnboardingOutcome::from($outcome);

        $data = ['onboarding_outcome' => $value];
        if ($user->onboarding_seen_at === null) {
            $data['onboarding_seen_at'] = now();
        }

        $user->forceFill($data)->save();
    }

    public function reset(User $user): void
    {
        $user->forceFill([
            'onboarding_seen_at' => null,
            'onboarding_outcome' => null,
        ])->save();
    }

    /**
     * @return list<array{target: string, title: string, content: string}>
     */
    public function getSteps(Plan|string $plan): array
    {
        $planValue = $plan instanceof Plan ? $plan->value : strtolower((string) $plan);

        $aiContent = match ($planValue) {
            'plus' => 'Ask questions, discover movie clips, and enjoy the higher Plus allowance.',
            'pro' => 'Perform smart searches, consult the film critic mode, and utilize premium AI features.',
            default => 'Ask questions about movies and find recommendations within your daily free allowance.',
        };

        $lastStep = match ($planValue) {
            'plus' => [
                'target' => 'navbar',
                'title' => 'Allowance meter',
                'content' => 'Track your remaining queries and monthly allowance directly from your account.',
            ],
            'pro' => [
                'target' => 'navbar',
                'title' => 'Smart searches',
                'content' => 'Monitor your remaining smart searches and advanced quota allocations.',
            ],
            default => [
                'target' => 'navbar',
                'title' => 'Subscription plans',
                'content' => 'Review available subscription plans to unlock additional daily queries and advanced search capabilities.',
            ],
        };

        return [
            [
                'target' => 'search',
                'title' => 'Search',
                'content' => 'Search for titles by name, cast, or director. Press slash at any time to open search.',
            ],
            [
                'target' => 'nav-links',
                'title' => 'Movies and TV shows',
                'content' => 'Explore movies and television shows with filters by genre, year, and rating.',
            ],
            [
                'target' => 'poster-card',
                'title' => 'Title cards',
                'content' => 'Open any title to view details, save it to your watchlist, or start a dedicated discussion.',
            ],
            [
                'target' => 'ai-button',
                'title' => 'AI assistant',
                'content' => $aiContent,
            ],
            [
                'target' => 'avatar-menu',
                'title' => 'Account menu',
                'content' => 'Access your watchlist, view your search history, and manage your subscription settings.',
            ],
            $lastStep,
        ];
    }
}
