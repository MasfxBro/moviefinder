<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('usage:prune')]
#[Description('Prune expired AI usage records')]
class PruneAiUsage extends Command
{
    public function handle(): int
    {
        $now = now(config('app.business_timezone', 'Asia/Jakarta'));
        DB::table('ai_usage_log')->where('created_at', '<', $now->copy()->subDays(30))->delete();
        DB::table('ai_usage')->where('used_on', '<', $now->copy()->subDays(14)->toDateString())->delete();
        DB::table('ai_free_places')->where('used_on', '<', $now->copy()->subDays(14)->toDateString())->delete();
        DB::table('ai_free_pool')->where('used_on', '<', $now->copy()->subDays(14)->toDateString())->delete();
        DB::table('ai_budget')->where('window_end', '<', $now->copy()->subMonths(14)->toDateString())->delete();

        return self::SUCCESS;
    }
}
