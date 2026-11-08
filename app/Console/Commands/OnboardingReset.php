<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Console\Command;

class OnboardingReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'onboarding:reset {user : The user ID or email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset guided tour status for a user';

    /**
     * Execute the console command.
     */
    public function handle(OnboardingService $onboardingService): int
    {
        $userArg = (string) $this->argument('user');

        $user = is_numeric($userArg)
            ? User::find((int) $userArg)
            : User::where('email', $userArg)->first();

        if (! $user) {
            $this->error("User not found: {$userArg}");

            return self::FAILURE;
        }

        $onboardingService->reset($user);

        $this->info("Reset onboarding for user {$user->id} ({$user->email}).");

        return self::SUCCESS;
    }
}
