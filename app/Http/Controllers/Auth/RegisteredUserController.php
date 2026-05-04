<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasskeyService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, PasskeyService $passkeys): RedirectResponse
    {
        if (RateLimiter::tooManyAttempts('register|' . $request->ip(), 3)) {
            throw ValidationException::withMessages([
                'username' => 'Too many registration attempts. Please try again later.',
            ]);
        }

        // MAX_USERS cap (C-20): 0 = unlimited.
        $maxUsers = (int) DB::table('settings')->where('key', 'max_users')->value('value');
        if ($maxUsers > 0 && User::count() >= $maxUsers) {
            throw ValidationException::withMessages([
                'username' => 'Registration is currently closed — the site has reached its maximum user count.',
            ]);
        }

        $request->validate([
            'username' => ['required', 'string', 'max:40', 'unique:users,username', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'passkey'  => $passkeys->generate(),
            'id_level' => 1,
        ]);

        RateLimiter::hit('register|' . $request->ip());

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
