<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\LinkReport;
use App\Models\TitleRequest;
use App\Models\User;
use App\Services\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(Settings $settings): View
    {
        // 1. Users
        $totalUsers = User::count();
        $newUsers7Days = User::where('created_at', '>=', now()->subDays(7))->count();

        // 2. Paid users by plan
        $paidUsers = ['plus' => 0, 'pro' => 0];
        if (Schema::hasTable('subscriptions')) {
            $planCounts = DB::table('subscriptions')
                ->whereIn('status', ['active', 'past_due'])
                ->where(function ($q): void {
                    $q->where('current_period_end', '>', now())
                        ->orWhere('grace_ends_at', '>', now());
                })
                ->groupBy('plan')
                ->select('plan', DB::raw('count(distinct user_id) as total'))
                ->pluck('total', 'plan');

            $paidUsers['plus'] = (int) ($planCounts['plus'] ?? 0);
            $paidUsers['pro'] = (int) ($planCounts['pro'] ?? 0);
        }

        // 3. Revenue, fees, AI cost, margin
        $revenueThisMonth = 0;
        $gatewayFeesThisMonth = 0;
        if (Schema::hasTable('payments')) {
            $revenueThisMonth = (int) DB::table('payments')
                ->where('status', 'paid')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount');

            $gatewayFeesThisMonth = (int) DB::table('payments')
                ->where('status', 'paid')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('est_fee_idr');
        }

        $aiCostThisMonth = 0;
        if (Schema::hasTable('ai_usage_log')) {
            $aiCostThisMonth = (int) DB::table('ai_usage_log')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('cost_idr');
        }

        $profitMargin = $revenueThisMonth - $aiCostThisMonth - $gatewayFeesThisMonth;

        // 4. AI usage today vs global cap
        $aiUsageToday = 0;
        if (Schema::hasTable('ai_global_usage')) {
            $aiUsageToday = (int) (DB::table('ai_global_usage')
                ->where('used_on', now()->toDateString())
                ->value('count') ?? 0);
        } elseif (Schema::hasTable('ai_usage_log')) {
            $aiUsageToday = (int) DB::table('ai_usage_log')
                ->where('created_at', '>=', now()->startOfDay())
                ->count();
        }
        $globalDailyCap = (int) $settings->get('ai.global_daily_cap', (int) config('ai.global_daily_cap', 1000));

        // 5. Clip resolver budget
        $clipResolverBudget = (int) $settings->get('clips.grounded_daily_budget', (int) config('clips.grounded_daily_budget', 0));

        // 6. Pending requests, reports, feedback
        $pendingRequests = Schema::hasTable('title_requests')
            ? TitleRequest::where('status', 'pending')->count()
            : 0;
        $pendingReports = Schema::hasTable('link_reports')
            ? LinkReport::where('status', 'open')->count()
            : 0;
        $pendingFeedback = Schema::hasTable('feedback')
            ? Feedback::where('status', 'new')->count()
            : 0;

        // 7. Top missed searches
        $topMissedSearches = Schema::hasTable('search_misses')
            ? DB::table('search_misses')
                ->where('status', 'open')
                ->orderByDesc('hits')
                ->limit(5)
                ->get()
            : collect();

        // 8. System health summary
        $dbConnected = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $dbConnected = false;
        }

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'newUsers7Days' => $newUsers7Days,
            'paidUsers' => $paidUsers,
            'revenueThisMonth' => $revenueThisMonth,
            'gatewayFeesThisMonth' => $gatewayFeesThisMonth,
            'aiCostThisMonth' => $aiCostThisMonth,
            'profitMargin' => $profitMargin,
            'aiUsageToday' => $aiUsageToday,
            'globalDailyCap' => $globalDailyCap,
            'clipResolverBudget' => $clipResolverBudget,
            'pendingRequests' => $pendingRequests,
            'pendingReports' => $pendingReports,
            'pendingFeedback' => $pendingFeedback,
            'topMissedSearches' => $topMissedSearches,
            'systemHealth' => [
                'database' => $dbConnected,
                'app_version' => config('app.version', '1.0.0'),
            ],
        ]);
    }
}
