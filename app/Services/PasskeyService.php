<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class PasskeyService
{
    /**
     * Generate a new 128-bit hex passkey (32 chars).
     * Replaces legacy md5(uniqid(rand())) which was 32-bit entropy.
     */
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Find a user by passkey, checking both the new and legacy columns.
     * On a legacy_passkey hit, logs a diagnostic warning so the user can be notified.
     */
    public function findUser(string $passkey): ?User
    {
        if (empty($passkey) || !preg_match('/^[A-Za-z0-9]{1,64}$/', $passkey)) {
            return null;
        }

        $user = User::where('passkey', $passkey)->first();

        if ($user) {
            return $user;
        }

        $user = User::where('legacy_passkey', $passkey)
            ->where(function ($q) {
                $q->whereNull('legacy_passkey_expires_at')
                  ->orWhere('legacy_passkey_expires_at', '>', now());
            })
            ->first();

        if ($user) {
            // Only log a prefix — never log the full passkey
            Log::info('Announce via legacy passkey', [
                'user_id' => $user->id,
                'hint'    => substr($passkey, 0, 6) . '…',
            ]);
        }

        return $user;
    }

    /**
     * Rotate a user's passkey (e.g. on password change).
     * Moves the current passkey to legacy_passkey with a 6-month expiry.
     */
    public function rotate(User $user): string
    {
        $newPasskey = $this->generate();

        $user->legacy_passkey            = $user->passkey;
        $user->legacy_passkey_expires_at = now()->addMonths(6);
        $user->passkey                   = $newPasskey;
        $user->save();

        return $newPasskey;
    }

    /**
     * Build the passkey validation regex for the current migration window.
     * Accepts old (hex MD5, 32 chars) and new (hex, 32 chars) — same pattern.
     * Tighten to /^[a-f0-9]{32}$/ once legacy_passkey column is dropped.
     */
    public function validationPattern(): string
    {
        return '/^[A-Za-z0-9]{1,64}$/';
    }
}
