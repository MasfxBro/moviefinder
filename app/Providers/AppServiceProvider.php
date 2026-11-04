<?php

namespace App\Providers;

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
        $this->app->singleton(\App\Contracts\MetadataProvider::class, function ($app) {
            return new \App\Services\Tmdb\TmdbClient(
                config('services.tmdb.token'),
                new \App\Services\Tmdb\TmdbMapper(),
            );
        });
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
