<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Ai\AiSearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class AiEvaluate extends Command
{
    protected $signature = 'ai:evaluate';

    protected $description = 'Evaluate AI title matching against the golden set';

    public function handle(AiSearchService $search): int
    {
        if (app()->environment('production')) {
            $this->error('This command is unavailable in production.');

            return self::FAILURE;
        }

        $fixture = json_decode(File::get(base_path('tests/Fixtures/ai_golden.json')), true, 512, JSON_THROW_ON_ERROR);
        $user = User::factory()->create(['role' => 'admin']);
        $total = count($fixture);
        $hits = 0;

        foreach ($fixture as $case) {
            try {
                $result = $search->search($user, $case['query']);
                $titles = array_map(static fn (array $title): string => mb_strtolower($title['title']), array_slice($result['titles'], 0, 3));
                $acceptable = array_map('mb_strtolower', $case['acceptable_titles']);
                $hits += (int) (array_intersect($acceptable, $titles) !== []);
            } catch (RuntimeException $exception) {
                $this->warn($case['query'].': '.$exception->getMessage());
            }
        }

        $rate = $total > 0 ? round($hits / $total * 100, 1) : 0;
        $this->line("Top-3 hit rate: {$hits}/{$total} ({$rate}%)");

        return self::SUCCESS;
    }
}
