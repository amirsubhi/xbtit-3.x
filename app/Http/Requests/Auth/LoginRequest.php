<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\LegacyPasswordVerifier;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Authenticate the user, handling both legacy MD5/SHA1 hashes and modern argon2id.
     * On a successful legacy login the password is immediately rehashed.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('username', $this->input('username'))->first();

        if (!$user || $user->isLocked()) {
            RateLimiter::hit($this->throttleKey());
            $this->fail();
        }

        $password = $this->input('password');
        $authenticated = false;

        if ($user->hasLegacyPassword()) {
            $authenticated = $this->authenticateLegacy($user, $password);
        } else {
            $authenticated = Hash::check($password, $user->password);
        }

        if (!$authenticated) {
            RateLimiter::hit($this->throttleKey());
            $user->incrementFailedLogins();

            if ($user->isLocked()) {
                $user->notify(new \App\Notifications\AccountLocked());
            }

            $this->fail();
        }

        RateLimiter::clear($this->throttleKey());
        $user->clearFailedLogins();

        DB::table('audit_logs')->insert([
            'user_id'    => $user->id,
            'action'     => 'login_success',
            'ip'         => $this->ip(),
            'created_at' => now(),
        ]);

        Auth::login($user, $this->boolean('remember'));
    }

    private function authenticateLegacy(User $user, string $password): bool
    {
        $verifier = app(LegacyPasswordVerifier::class);

        if ($verifier->requiresPasswordReset($user->pass_type)) {
            // Type 7 has no implementation — cannot verify, must reset
            return false;
        }

        $sitesecret = DB::table('settings')->where('key', 'secsui_ss')->value('value') ?? '';

        $valid = $verifier->verify(
            plaintext: $password,
            stored:    $user->password,
            passType:  $user->pass_type,
            salt:      $user->salt,
            username:  $user->username,
            sitesecret: $sitesecret,
        );

        if ($valid) {
            // Rehash to argon2id immediately and clear the legacy type
            $user->password   = Hash::make($password);
            $user->pass_type  = null;
            $user->salt       = '';
            $user->save();
        }

        return $valid;
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')) . '|' . $this->ip());
    }

    private function fail(): never
    {
        throw ValidationException::withMessages([
            'username' => trans('auth.failed'),
        ]);
    }
}
