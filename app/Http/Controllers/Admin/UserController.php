<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('level')
            ->when($request->search, fn ($q) => $q->where('username', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(30);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $levels = UserLevel::orderBy('level')->get();
        $user->load('level');
        return view('admin.users.show', compact('user', 'levels'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'id_level' => ['required', 'string', 'exists:users_level,id_level'],
            'locked'   => ['boolean'],
            'note'     => ['nullable', 'string', 'max:500'],
        ]);

        $user->id_level  = $data['id_level'];
        $user->locked_at = !empty($data['locked']) ? ($user->locked_at ?? now()) : null;
        $user->save();

        AuditLog::record('admin.user.update', ['target_user_id' => $user->id, 'id_level' => $data['id_level']]);

        return back()->with('status', 'User updated.');
    }

    public function resetPassword(User $user)
    {
        \Password::sendResetLink(['email' => $user->email]);
        return back()->with('status', 'Password reset email sent.');
    }
}
