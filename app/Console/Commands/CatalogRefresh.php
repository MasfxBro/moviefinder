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
        $limit = (int) $this->option('limit');
        $this->info("Refreshing up to {$limit} oldest TMDB titles...");

        $count = $catalogService->refresh($limit);

        $this->info("Refreshed {$count} titles successfully.");

        return Command::SUCCESS;
    }
}
