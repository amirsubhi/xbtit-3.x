<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckIpBan
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $ipLong = sprintf('%u', ip2long($ip));

        if ($ipLong && $this->isBanned($ipLong)) {
            abort(403, 'Your IP address has been banned.');
        }

        return $next($request);
    }

    private function isBanned(string $ipLong): bool
    {
        return DB::table('banned_ip')
            ->where('first', '<=', $ipLong)
            ->where('last', '>=', $ipLong)
            ->exists();
    }
}
