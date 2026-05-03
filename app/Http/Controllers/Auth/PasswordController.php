<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasskeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function update(Request $request, PasskeyService $passkeys): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        $user->update(['password' => Hash::make($validated['password'])]);

        // Rotate passkey on password change so old .torrent files stop working
        $passkeys->rotate($user);

        return back()->with('status', 'password-updated');
    }
}
