<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Catalog\CatalogService;
use Illuminate\Console\Command;

class CatalogSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:seed {--limit=20 : Number of popular titles per media type to seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed catalog with popular titles from TMDB';

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
        $this->info("Seeding popular titles (up to {$limit} per media type)...");

        $count = $catalogService->seedPopular($limit);

        $this->info("Seeded {$count} titles successfully.");

        return Command::SUCCESS;
    }
}
