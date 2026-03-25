<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('openai-email-generation', function (): Limit {
            return Limit::perMinute((int) env('MASS_EMAIL_GENERATION_RATE_PER_MINUTE', 20))
                ->by('global-openai-email-generation');
        });

        RateLimiter::for('email-send', function (): Limit {
            return Limit::perMinute((int) env('MASS_EMAILS_RATE_PER_MINUTE', 30))
                ->by('global-email-send');
        });
    }
}
