<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackOnlineUsers
{
    // 60-second cooldown between record updates (C-28).
    private const COOLDOWN_SECONDS  = 60;
    // Records older than 900 s (15 min) are considered offline.
    private const EXPIRY_SECONDS    = 900;

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasSession() || !auth()->check()) {
            return $next($request);
        }

        $now    = time();
        $lastTs = $request->session()->get('online_last_update', 0);

        if ($now - $lastTs >= self::COOLDOWN_SECONDS) {
            $user      = auth()->user();
            $sessionId = session()->getId();
            $ip        = $request->ip();
            $location  = $request->path();

            DB::table('online')->updateOrInsert(
                ['session_id' => $sessionId],
                [
                    'uid'        => $user->id,
                    'user_name'  => $user->username,
                    'user_ip'    => $ip,
                    'user_group' => $user->id_level,
                    'location'   => substr($location, 0, 255),
                    'lastaction' => $now,
                ]
            );

            // Purge expired sessions (avoids a separate scheduled job).
            DB::table('online')->where('lastaction', '<', $now - self::EXPIRY_SECONDS)->delete();

            $request->session()->put('online_last_update', $now);
        }

        return $next($request);
    }
}
