<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // 5 attempts per minute per IP+username combination
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->input('username', '') . '|' . $request->ip());
        });

        // 3 registrations per hour per IP
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // 3 password reset requests per hour per email address
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(3)->by($request->input('email', ''));
        });
    }
}
