<?php

use App\Http\Middleware\CheckIpBan;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Tracker routes run with NO middleware — no session, CSRF, cookies, auth, or rate limiting.
            // AnnounceService and ScrapeController handle their own IP ban and auth checks.
            Route::middleware([])->group(base_path('routes/tracker.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Security headers on every response
        $middleware->append(SecurityHeaders::class);

        // IP ban check on every web request
        $middleware->appendToGroup('web', CheckIpBan::class);

        // Apply user/session locale on every web request
        $middleware->appendToGroup('web', SetLocale::class);

        // Route middleware aliases
        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);

        // Announce and scrape endpoints are stateless BitTorrent protocol —
        // they must skip CSRF, sessions, and cookies entirely
        $middleware->validateCsrfTokens(except: [
            '/announce',
            '/announce.php',
            '/scrape',
            '/scrape.php',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
