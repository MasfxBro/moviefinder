<?php

namespace App\Providers;

use App\Contracts\ClipFinder;
use App\Contracts\GroundedLlmClient;
use App\Contracts\LlmClient;
use App\Contracts\MetadataProvider;
use App\Contracts\PlanResolver;
use App\Enums\Plan;
use App\Models\SearchHistory;
use App\Models\TitleRequest;
use App\Models\Watchlist;
use App\Policies\SearchHistoryPolicy;
use App\Policies\TitleRequestPolicy;
use App\Policies\WatchlistPolicy;
use App\Services\Ai\FakeLlmClient;
use App\Services\Ai\FreePlanResolver;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\PlanCatalog;
use App\Services\Clips\AiSearchClipFinder;
use App\Services\Clips\ClipFinderChain;
use App\Services\Clips\CuratedClipFinder;
use App\Services\Clips\SearchLinkFinder;
use App\Services\Clips\StoredClipFinder;
use App\Services\Tmdb\TmdbClient;
use App\Services\Tmdb\TmdbMapper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MetadataProvider::class, function ($app) {
            return new TmdbClient(
                config('services.tmdb.token'),
                new TmdbMapper,
            );
        });

        $this->app->bind(LlmClient::class, fn () => config('ai.driver') === 'fake' ? new FakeLlmClient : new GeminiClient);
        $this->app->bind(GroundedLlmClient::class, GeminiClient::class);
        $this->app->bind(ClipFinder::class, fn ($app) => new ClipFinderChain([
            $app->make(CuratedClipFinder::class),
            $app->make(StoredClipFinder::class),
            $app->make(AiSearchClipFinder::class),
            $app->make(SearchLinkFinder::class),
        ]));
        $this->app->bind(PlanResolver::class, FreePlanResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Watchlist::class, WatchlistPolicy::class);
        Gate::policy(SearchHistory::class, SearchHistoryPolicy::class);
        Gate::policy(TitleRequest::class, TitleRequestPolicy::class);

        Password::defaults(function () {
            return Password::min(10)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });

        // Rate limiter for email verification resend (SECURITY.md section 6)
        RateLimiter::for('ai-messages', function ($request) {
            $plan = $request->user() ? app(PlanResolver::class)->resolve($request->user()) : Plan::Free;
            $limit = (int) (app(PlanCatalog::class)->get($plan)['ai_rate_limit_per_minute'] ?? 6);

            return Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('verify-resend', function ($request) {
            $limits = [];

            if ($request->user()) {
                $limits[] = Limit::perHour(3)->by($request->user()->id);
            }

            $limits[] = Limit::perHour(5)->by($request->ip());

            return $limits;
        });

        RateLimiter::for('report-link', function ($request) {
            return Limit::perDay(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('request-title', function ($request) {
            $user = $request->user();
            $plan = $user ? app(PlanResolver::class)->resolve($user) : Plan::Free;
            $limit = (int) (app(PlanCatalog::class)->get($plan)['requests_daily'] ?? 2);

            return Limit::perDay($limit)->by($user?->id ?: $request->ip());
        });
    }
}
