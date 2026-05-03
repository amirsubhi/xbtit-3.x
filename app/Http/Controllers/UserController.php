<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasskeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly PasskeyService $passkeys) {}

    public function show(int $id): View
    {
        $profile = User::with('level')->findOrFail($id);
        $snatches = $profile->snatches()->with('torrent')->latest('date')->limit(20)->get();

        return view('users.show', compact('profile', 'snatches'));
    }

    public function edit(Request $request): View
    {
        return view('users.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'email'  => ['required', 'email', 'max:100', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['sometimes', 'nullable', 'url', 'max:200'],
        ]);

        $user->update([
            'email'  => $request->input('email'),
            'avatar' => $request->input('avatar'),
        ]);

        return back()->with('status', 'Profile updated.');
    }

    public function regeneratePasskey(Request $request): RedirectResponse
    {
        $this->passkeys->rotate($request->user());

        return back()->with('status', 'Passkey regenerated. Re-download your torrents to continue seeding.');
    }
}
