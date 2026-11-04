<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PruneUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:prune-unverified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete unverified accounts older than the configured TTL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ttlDays = config('auth.unverified_ttl_days', 14);
        $cutoffDate = now()->subDays($ttlDays);

        $deleted = User::whereNull('email_verified_at')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $this->info("Deleted {$deleted} unverified account(s) older than {$ttlDays} days.");

        return self::SUCCESS;
    }
}
