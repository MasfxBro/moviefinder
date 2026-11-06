<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Catalog\CatalogService;
use Illuminate\Console\Command;

class CatalogRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:refresh {--limit=100 : Maximum number of titles to refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh oldest TMDB-derived rows so none is older than 150 days';

    /**
     * Execute the console command.
     */
    public function handle(CatalogService $catalogService): int
    {
        $option = filter_var($this->option('limit'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => (int) config('catalog.max_command_limit', 500)],
        ]);
        if ($option === false) {
            $this->error('The limit must be a positive integer within the configured maximum.');

            return Command::INVALID;
        }

        $limit = $option;
        $this->info("Refreshing up to {$limit} oldest TMDB titles...");

        $count = $catalogService->refresh($limit);

        $this->info("Refreshed {$count} titles successfully.");

        return Command::SUCCESS;
    }
}
