<?php

namespace App\Providers;

use App\Contracts\LlmClient;
use App\Contracts\MetadataProvider;
use App\Contracts\PlanResolver;
use App\Services\Ai\FakeLlmClient;
use App\Services\Ai\FreePlanResolver;
use App\Services\Ai\GeminiClient;
use App\Services\Tmdb\TmdbClient;
use App\Services\Tmdb\TmdbMapper;
use Illuminate\Cache\RateLimiting\Limit;
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
        $this->app->bind(PlanResolver::class, FreePlanResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(10)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });

        // Rate limiter for email verification resend (SECURITY.md section 6)
        RateLimiter::for('verify-resend', function ($request) {
            $limits = [];

            if ($request->user()) {
                $limits[] = Limit::perHour(3)->by($request->user()->id);
            }

            $limits[] = Limit::perHour(5)->by($request->ip());

            return $limits;
        });
    }
}
