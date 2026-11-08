<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SearchHistory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('history:prune')]
#[Description('Delete search history older than 90 days')]
class PruneSearchHistory extends Command
{
    public function handle(): int
    {
        $cutoff = now()->subDays(90);

        $deleted = SearchHistory::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} search history records older than 90 days.");

        return self::SUCCESS;
    }
}
