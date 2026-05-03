<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    const SUPPORTED = ['en', 'es', 'zh', 'ar', 'fr', 'pt', 'ms'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = auth()->user()?->locale
            ?? session('locale')
            ?? config('app.locale', 'en');

        if (in_array($locale, self::SUPPORTED, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
